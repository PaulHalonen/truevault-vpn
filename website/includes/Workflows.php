<?php
/**
 * TrueVault VPN - Automated Workflows
 * 12 business automation workflows
 * 
 * @package TrueVault
 * @version 2.0.0
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AutomationEngine.php';

class Workflows {
    private $engine;
    private $mainDb;
    private $billingDb;
    
    public function __construct() {
        $this->engine = new AutomationEngine();
        $this->mainDb = new Database('main');
        $this->billingDb = new Database('billing');
    }
    
    // ============================================
    // WORKFLOW 1: NEW CUSTOMER ONBOARDING
    // ============================================
    
    /**
     * Triggered when a new customer signs up
     */
    public function newCustomerOnboarding($userId, $email, $firstName, $planName) {
        $this->engine->startWorkflow('new_customer_onboarding', 'signup', $userId, $email, [
            'first_name' => $firstName,
            'plan_name' => $planName
        ]);
        
        try {
            $variables = [
                'first_name' => $firstName,
                'email' => $email,
                'plan_name' => $planName
            ];
            
            // Step 1: Send welcome email immediately
            $template = ($planName === 'Family' || $planName === 'Dedicated') 
                ? 'welcome_formal' 
                : 'welcome_basic';
            
            $this->engine->sendEmail($email, $template, $variables);
            
            // Step 2: Notify admin of new signup
            $this->engine->sendEmail($email, 'admin_new_signup', $variables, true);
            
            // Step 3: Schedule setup guide email (1 hour later)
            // Note: Using welcome email again as setup guide - could create separate template
            
            // Step 4: Schedule 24-hour follow-up check-in
            $this->engine->scheduleTask(
                'follow_up_checkin',
                'email',
                [
                    'to' => $email,
                    'template' => 'welcome_basic',
                    'variables' => array_merge($variables, [
                        'subject_override' => 'How\'s your TrueVault VPN experience?'
                    ])
                ],
                1440 // 24 hours in minutes
            );
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // WORKFLOW 2: PAYMENT FAILED ESCALATION
    // ============================================
    
    /**
     * Triggered when a payment fails
     */
    public function paymentFailedEscalation($userId, $email, $firstName, $amount, $transactionId = null) {
        $this->engine->startWorkflow('payment_failed_escalation', 'payment_failed', $userId, $email, [
            'amount' => $amount,
            'transaction_id' => $transactionId
        ]);
        
        try {
            $variables = [
                'first_name' => $firstName,
                'email' => $email,
                'amount' => $amount
            ];
            
            // Step 1: Update status to grace_period
            $this->engine->updateUserStatus($userId, 'grace_period');
            
            // Step 2: Send friendly reminder (Day 0)
            $this->engine->sendEmail($email, 'payment_failed_reminder1', $variables);
            
            // Step 3: Schedule urgent notice (Day 3)
            $this->engine->scheduleEmail(
                $email,
                'payment_failed_reminder2',
                $variables,
                4320 // 3 days in minutes
            );
            
            // Step 4: Schedule final warning (Day 7)
            $this->engine->scheduleEmail(
                $email,
                'payment_failed_final',
                $variables,
                10080 // 7 days in minutes
            );
            
            // Step 5: Schedule suspension (Day 8)
            $this->engine->scheduleTask(
                'suspend_for_nonpayment',
                'suspend_account',
                ['user_id' => $userId],
                11520 // 8 days in minutes
            );
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // WORKFLOW 3: PAYMENT SUCCESS
    // ============================================
    
    /**
     * Triggered when payment is received
     */
    public function paymentSuccess($userId, $email, $firstName, $amount, $transactionId, $planName) {
        $this->engine->startWorkflow('payment_success', 'payment_received', $userId, $email, [
            'amount' => $amount,
            'transaction_id' => $transactionId
        ]);
        
        try {
            $variables = [
                'first_name' => $firstName,
                'email' => $email,
                'amount' => $amount,
                'transaction_id' => $transactionId,
                'plan_name' => $planName
            ];
            
            // Step 1: Send thank you email
            $this->engine->sendEmail($email, 'payment_success', $variables);
            
            // Step 2: Update status to active (in case was in grace period)
            $this->engine->updateUserStatus($userId, 'active');
            
            // Step 3: Cancel any pending suspension tasks
            $this->cancelPendingSuspension($userId);
            
            // Step 4: Notify admin
            $this->engine->sendEmail($email, 'admin_payment_received', $variables, true);
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel pending suspension for user
     */
    private function cancelPendingSuspension($userId) {
        $logsDb = new Database('logs');
        $stmt = $logsDb->prepare("
            UPDATE scheduled_tasks 
            SET status = 'cancelled'
            WHERE task_type = 'suspend_account' 
            AND status = 'pending'
            AND task_data LIKE ?
        ");
        $stmt->execute(['%"user_id":' . $userId . '%']);
    }
    
    // ============================================
    // WORKFLOW 4: SUPPORT TICKET CREATED
    // ============================================
    
    /**
     * Triggered when support ticket is created
     */
    public function supportTicketCreated($ticketId, $userId, $email, $firstName, $subject, $message) {
        $this->engine->startWorkflow('support_ticket_created', 'ticket_created', $userId, $email, [
            'ticket_id' => $ticketId,
            'subject' => $subject
        ]);
        
        try {
            // Step 1: Auto-categorize ticket
            $category = $this->categorizeTicket($subject, $message);
            $this->updateTicketCategory($ticketId, $category);
            
            // Step 2: Check if user is VIP
            $isVip = $this->isVipUser($email);
            $priority = $isVip ? 'high' : 'normal';
            
            // Step 3: Check for complaint keywords
            if ($this->isComplaint($subject, $message)) {
                $priority = 'urgent';
                // Trigger complaint workflow
                $this->complaintHandling($ticketId, $userId, $email, $firstName, $subject, $message);
            }
            
            $this->updateTicketPriority($ticketId, $priority);
            
            $variables = [
                'first_name' => $firstName,
                'email' => $email,
                'ticket_id' => $ticketId,
                'ticket_subject' => $subject
            ];
            
            // Step 4: Send acknowledgment email
            $this->engine->sendEmail($email, 'ticket_received', $variables);
            
            // Step 5: Schedule escalation if not resolved in 24 hours
            $this->engine->scheduleTask(
                'escalate_ticket',
                'webhook',
                [
                    'url' => 'internal://escalate_ticket',
                    'payload' => ['ticket_id' => $ticketId]
                ],
                1440 // 24 hours
            );
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Categorize ticket based on keywords
     */
    private function categorizeTicket($subject, $message) {
        $text = strtolower($subject . ' ' . $message);
        
        $billingKeywords = ['payment', 'bill', 'charge', 'refund', 'subscription', 'cancel', 'price', 'cost', 'invoice'];
        $technicalKeywords = ['connect', 'speed', 'slow', 'error', 'not working', 'issue', 'problem', 'server', 'vpn', 'wireguard'];
        $accountKeywords = ['login', 'password', 'email', 'account', 'change', 'update', 'profile'];
        
        foreach ($billingKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) return 'billing';
        }
        
        foreach ($technicalKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) return 'technical';
        }
        
        foreach ($accountKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) return 'account';
        }
        
        return 'general';
    }
    
    /**
     * Check if message is a complaint
     */
    private function isComplaint($subject, $message) {
        $text = strtolower($subject . ' ' . $message);
        $complaintKeywords = ['complaint', 'terrible', 'awful', 'horrible', 'worst', 'angry', 'frustrated', 'unacceptable', 'demand', 'lawyer', 'refund'];
        
        foreach ($complaintKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) return true;
        }
        
        return false;
    }
    
    /**
     * Check if user is VIP
     */
    private function isVipUser($email) {
        $stmt = $this->mainDb->prepare("SELECT 1 FROM vip_list WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Update ticket category
     */
    private function updateTicketCategory($ticketId, $category) {
        try {
            $supportDb = new Database('support');
            $stmt = $supportDb->prepare("UPDATE support_tickets SET category = ? WHERE id = ?");
            $stmt->execute([$category, $ticketId]);
        } catch (Exception $e) {
            // Support DB might not exist yet
        }
    }
    
    /**
     * Update ticket priority
     */
    private function updateTicketPriority($ticketId, $priority) {
        try {
            $supportDb = new Database('support');
            $stmt = $supportDb->prepare("UPDATE support_tickets SET priority = ? WHERE id = ?");
            $stmt->execute([$priority, $ticketId]);
        } catch (Exception $e) {
            // Support DB might not exist yet
        }
    }
    
    // ============================================
    // WORKFLOW 5: SUPPORT TICKET RESOLVED
    // ============================================
    
    /**
     * Triggered when ticket is marked resolved
     */
    public function supportTicketResolved($ticketId, $userId, $email, $firstName, $subject) {
        $this->engine->startWorkflow('support_ticket_resolved', 'ticket_resolved', $userId, $email, [
            'ticket_id' => $ticketId
        ]);
        
        try {
            $variables = [
                'first_name' => $firstName,
                'email' => $email,
                'ticket_id' => $ticketId,
                'ticket_subject' => $subject
            ];
            
            // Step 1: Send resolution notification
            $this->engine->sendEmail($email, 'ticket_resolved', $variables);
            
            // Step 2: Schedule satisfaction survey (1 hour later)
            // Could create a dedicated survey template
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // WORKFLOW 6: COMPLAINT HANDLING
    // ============================================
    
    /**
     * Triggered when complaint is detected
     */
    public function complaintHandling($ticketId, $userId, $email, $firstName, $subject, $message) {
        $this->engine->startWorkflow('complaint_handling', 'complaint_received', $userId, $email, [
            'ticket_id' => $ticketId,
            'subject' => $subject
        ]);
        
        try {
            $variables = [
                'first_name' => $firstName,
                'email' => $email,
                'ticket_id' => $ticketId,
                'ticket_subject' => $subject,
                'message' => $message
            ];
            
            // Step 1: Send apology/acknowledgment
            $this->engine->sendEmail($email, 'complaint_acknowledge', $variables);
            
            // Step 2: Notify admin IMMEDIATELY
            $this->engine->sendEmail($email, 'admin_complaint', $variables, true);
            
            // Step 3: Schedule 7-day follow-up
            $this->engine->scheduleEmail(
                $email,
                'ticket_resolved', // Use resolution template for follow-up
                $variables,
                10080 // 7 days
            );
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // WORKFLOW 7: SERVER DOWN ALERT
    // ============================================
    
    /**
     * Triggered when server goes offline
     */
    public function serverDownAlert($serverName, $serverIp, $isPlannedMaintenance = false) {
        $this->engine->startWorkflow('server_down_alert', 'server_offline', null, null, [
            'server_name' => $serverName,
            'server_ip' => $serverIp,
            'planned' => $isPlannedMaintenance
        ]);
        
        try {
            $variables = [
                'server_name' => $serverName,
                'server_ip' => $serverIp,
                'status' => 'OFFLINE'
            ];
            
            // Step 1: Alert admin immediately
            $this->engine->sendEmail(null, 'admin_server_alert', $variables, true);
            
            // Step 2: If unplanned, notify affected customers
            if (!$isPlannedMaintenance) {
                $this->notifyAffectedUsers($serverName, $serverIp);
            }
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify users affected by server outage
     */
    private function notifyAffectedUsers($serverName, $serverIp) {
        // Get users with devices on this server
        try {
            $devicesDb = new Database('devices');
            $stmt = $devicesDb->prepare("
                SELECT DISTINCT u.id, u.email, u.name 
                FROM devices d
                JOIN users u ON d.user_id = u.id
                WHERE d.server_ip = ? AND d.is_active = 1
            ");
            $stmt->execute([$serverIp]);
            $users = $stmt->fetchAll();
            
            foreach ($users as $user) {
                $this->engine->sendEmail($user['email'], 'server_down', [
                    'first_name' => $user['name'] ?? 'Customer',
                    'server_name' => $serverName
                ]);
            }
        } catch (Exception $e) {
            $this->engine->log("Error notifying users: " . $e->getMessage(), 'error');
        }
    }
    
    // ============================================
    // WORKFLOW 8: SERVER RESTORED
    // ============================================
    
    /**
     * Triggered when server comes back online
     */
    public function serverRestored($serverName, $serverIp) {
        $this->engine->startWorkflow('server_restored', 'server_online', null, null, [
            'server_name' => $serverName,
            'server_ip' => $serverIp
        ]);
        
        try {
            $variables = [
                'server_name' => $serverName,
                'server_ip' => $serverIp,
                'status' => 'ONLINE'
            ];
            
            // Step 1: Notify admin
            $this->engine->sendEmail(null, 'admin_server_alert', array_merge($variables, [
                'status' => 'RESTORED'
            ]), true);
            
            // Step 2: Notify affected customers
            $this->notifyUsersServerRestored($serverName, $serverIp);
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify users server is restored
     */
    private function notifyUsersServerRestored($serverName, $serverIp) {
        try {
            $devicesDb = new Database('devices');
            $stmt = $devicesDb->prepare("
                SELECT DISTINCT u.id, u.email, u.name 
                FROM devices d
                JOIN users u ON d.user_id = u.id
                WHERE d.server_ip = ? AND d.is_active = 1
            ");
            $stmt->execute([$serverIp]);
            $users = $stmt->fetchAll();
            
            foreach ($users as $user) {
                $this->engine->sendEmail($user['email'], 'server_restored', [
                    'first_name' => $user['name'] ?? 'Customer',
                    'server_name' => $serverName
                ]);
            }
        } catch (Exception $e) {
            $this->engine->log("Error notifying users: " . $e->getMessage(), 'error');
        }
    }
    
    // ============================================
    // WORKFLOW 9: CANCELLATION REQUEST
    // ============================================
    
    /**
     * Triggered when customer requests cancellation
     */
    public function cancellationRequest($userId, $email, $firstName, $planName, $endDate) {
        $this->engine->startWorkflow('cancellation_request', 'cancel_requested', $userId, $email, [
            'plan_name' => $planName,
            'end_date' => $endDate
        ]);
        
        try {
            $variables = [
                'first_name' => $firstName,
                'email' => $email,
                'plan_name' => $planName,
                'end_date' => $endDate,
                'discounted_price' => $this->getDiscountedPrice($planName)
            ];
            
            // Step 1: Send exit survey
            $this->engine->sendEmail($email, 'cancellation_survey', $variables);
            
            // Step 2: Send retention offer (1 hour later)
            $this->engine->scheduleEmail($email, 'retention_offer', $variables, 60);
            
            // Step 3: Schedule win-back campaign (30 days after end date)
            $endTimestamp = strtotime($endDate);
            $winbackDelay = (($endTimestamp - time()) / 60) + 43200; // 30 days after end
            
            if ($winbackDelay > 0) {
                $this->engine->scheduleEmail($email, 'winback_campaign', $variables, (int)$winbackDelay);
            }
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get discounted price for retention offer
     */
    private function getDiscountedPrice($planName) {
        $prices = [
            'Personal' => 9.97,
            'Family' => 14.97,
            'Dedicated' => 39.97
        ];
        
        $original = $prices[$planName] ?? 9.97;
        return number_format($original * 0.5, 2); // 50% off
    }
    
    // ============================================
    // WORKFLOW 10: MONTHLY INVOICING
    // ============================================
    
    /**
     * Triggered at end of month for batch invoicing
     */
    public function monthlyInvoicing() {
        $this->engine->startWorkflow('monthly_invoicing', 'end_of_month', null, null, [
            'month' => date('F Y')
        ]);
        
        try {
            // Get all active subscriptions
            $stmt = $this->billingDb->query("
                SELECT s.*, u.email, u.name, u.tier
                FROM subscriptions s
                JOIN users u ON s.user_id = u.id
                WHERE s.status = 'active'
            ");
            $subscriptions = $stmt->fetchAll();
            
            $invoiced = 0;
            
            foreach ($subscriptions as $sub) {
                // PayPal handles actual billing, this is for notifications
                $variables = [
                    'first_name' => $sub['name'] ?? 'Customer',
                    'email' => $sub['email'],
                    'plan_name' => ucfirst($sub['tier']),
                    'amount' => $sub['amount'] ?? '9.97'
                ];
                
                // Send payment reminder/invoice
                $this->engine->sendEmail($sub['email'], 'payment_success', $variables);
                $invoiced++;
            }
            
            $this->engine->log("Monthly invoicing complete: $invoiced customers processed");
            $this->engine->completeWorkflow(true);
            return $invoiced;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return 0;
        }
    }
    
    // ============================================
    // WORKFLOW 11: VIP ADDED (SECRET - ADMIN ONLY)
    // ============================================
    
    /**
     * Triggered when admin adds user to VIP list
     * NOTE: This is completely internal - no public visibility
     */
    public function vipAdded($email) {
        // Find user if exists
        $stmt = $this->mainDb->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        $userId = $user ? $user['id'] : null;
        $firstName = $user ? ($user['name'] ?? 'Valued Customer') : 'Valued Customer';
        
        $this->engine->startWorkflow('vip_added', 'admin_action', $userId, $email, []);
        
        try {
            // If user exists, update their tier
            if ($userId) {
                $stmt = $this->mainDb->prepare("UPDATE users SET tier = 'vip' WHERE id = ?");
                $stmt->execute([$userId]);
            }
            
            // Log VIP addition (admin notification only)
            $this->engine->log("VIP status granted to: $email");
            
            // Note: No email sent to user - they discover VIP on login
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
    
    // ============================================
    // WORKFLOW 12: VIP REMOVED (SECRET - ADMIN ONLY)
    // ============================================
    
    /**
     * Triggered when admin removes user from VIP list
     */
    public function vipRemoved($email) {
        $stmt = $this->mainDb->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        $this->engine->startWorkflow('vip_removed', 'admin_action', $user['id'] ?? null, $email, []);
        
        try {
            // If user exists, revert their tier
            if ($user) {
                $stmt = $this->mainDb->prepare("UPDATE users SET tier = 'free' WHERE id = ?");
                $stmt->execute([$user['id']]);
            }
            
            $this->engine->log("VIP status removed from: $email");
            
            $this->engine->completeWorkflow(true);
            return true;
            
        } catch (Exception $e) {
            $this->engine->completeWorkflow(false, $e->getMessage());
            return false;
        }
    }
}
