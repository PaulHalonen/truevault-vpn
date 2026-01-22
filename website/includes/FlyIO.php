<?php
/**
 * Fly.io API Helper Class
 * 
 * PURPOSE: Interact with Fly.io GraphQL API for server management
 * - Get app status
 * - Restart apps
 * - Get metrics
 * 
 * NOTE: Fly.io API token stored in database (business_settings)
 * 
 * @created January 23, 2026
 * @version 1.0.0
 */

if (!defined('TRUEVAULT_INIT')) {
    http_response_code(403);
    die('Direct access not allowed');
}

class FlyIO {
    
    private static $graphqlUrl = 'https://api.fly.io/graphql';
    
    /**
     * Get API token from database
     */
    private static function getApiToken() {
        $settingsDb = Database::getInstance('settings');
        $stmt = $settingsDb->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
        $stmt->bindValue(':key', 'flyio_api_token', SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$row || !$row['setting_value']) {
            throw new Exception('Fly.io API token not configured');
        }
        
        return $row['setting_value'];
    }
    
    /**
     * Make GraphQL request to Fly.io
     */
    private static function graphqlRequest($query, $variables = []) {
        $token = self::getApiToken();
        
        $ch = curl_init(self::$graphqlUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'query' => $query,
            'variables' => $variables
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("Fly.io API error: HTTP {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['errors']) && count($data['errors']) > 0) {
            throw new Exception("GraphQL error: " . ($data['errors'][0]['message'] ?? 'Unknown'));
        }
        
        return $data['data'] ?? [];
    }
    
    /**
     * Get app status
     * 
     * @param string $appName Fly.io app name
     * @return array App status
     */
    public static function getAppStatus($appName) {
        $query = '
            query GetApp($appName: String!) {
                app(name: $appName) {
                    id
                    name
                    status
                    deployed
                    hostname
                    organization {
                        slug
                    }
                    currentRelease {
                        id
                        version
                        status
                        createdAt
                    }
                    machines {
                        nodes {
                            id
                            name
                            state
                            region
                            instanceId
                            privateIP
                            createdAt
                            updatedAt
                        }
                    }
                }
            }
        ';
        
        try {
            $data = self::graphqlRequest($query, ['appName' => $appName]);
            
            if (!isset($data['app'])) {
                return ['success' => false, 'error' => 'App not found'];
            }
            
            $app = $data['app'];
            $machines = $app['machines']['nodes'] ?? [];
            
            return [
                'success' => true,
                'app_id' => $app['id'],
                'name' => $app['name'],
                'status' => $app['status'],
                'deployed' => $app['deployed'],
                'hostname' => $app['hostname'],
                'organization' => $app['organization']['slug'] ?? '',
                'current_release' => $app['currentRelease'] ?? null,
                'machines' => $machines,
                'machine_count' => count($machines)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Restart an app
     * 
     * @param string $appName Fly.io app name
     * @return array Result
     */
    public static function restartApp($appName) {
        $query = '
            mutation RestartApp($appName: String!) {
                restartApp(appName: $appName) {
                    app {
                        id
                        name
                        status
                    }
                }
            }
        ';
        
        try {
            $data = self::graphqlRequest($query, ['appName' => $appName]);
            return [
                'success' => true,
                'message' => 'App restart initiated',
                'app' => $data['restartApp']['app'] ?? null
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get app metrics
     * 
     * @param string $appName Fly.io app name
     * @return array Metrics
     */
    public static function getAppMetrics($appName) {
        // Basic metrics query
        $query = '
            query GetAppMetrics($appName: String!) {
                app(name: $appName) {
                    id
                    name
                    machines {
                        nodes {
                            id
                            state
                            region
                            checks {
                                name
                                status
                                output
                            }
                        }
                    }
                    processGroups {
                        name
                        regions
                    }
                    services {
                        protocol
                        internalPort
                        ports {
                            port
                            handlers
                        }
                    }
                }
            }
        ';
        
        try {
            $data = self::graphqlRequest($query, ['appName' => $appName]);
            
            if (!isset($data['app'])) {
                return ['success' => false, 'error' => 'App not found'];
            }
            
            $app = $data['app'];
            
            return [
                'success' => true,
                'app_name' => $app['name'],
                'machines' => $app['machines']['nodes'] ?? [],
                'process_groups' => $app['processGroups'] ?? [],
                'services' => $app['services'] ?? []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * List all apps
     * 
     * @return array List of apps
     */
    public static function listApps() {
        $query = '
            query {
                apps {
                    nodes {
                        id
                        name
                        status
                        deployed
                        hostname
                        organization {
                            slug
                        }
                    }
                }
            }
        ';
        
        try {
            $data = self::graphqlRequest($query);
            return [
                'success' => true,
                'apps' => $data['apps']['nodes'] ?? []
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get machine details
     * 
     * @param string $appName App name
     * @param string $machineId Machine ID
     * @return array Machine details
     */
    public static function getMachine($appName, $machineId) {
        $query = '
            query GetMachine($appName: String!, $machineId: String!) {
                app(name: $appName) {
                    machine(id: $machineId) {
                        id
                        name
                        state
                        region
                        instanceId
                        privateIP
                        config {
                            image
                            env
                            services {
                                protocol
                                internalPort
                            }
                        }
                        createdAt
                        updatedAt
                    }
                }
            }
        ';
        
        try {
            $data = self::graphqlRequest($query, [
                'appName' => $appName,
                'machineId' => $machineId
            ]);
            
            return [
                'success' => true,
                'machine' => $data['app']['machine'] ?? null
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
