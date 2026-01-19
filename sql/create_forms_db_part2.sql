-- Additional Form Templates (Part 2)
-- Continuing to 50+ templates

-- SURVEY FORMS (12)
INSERT OR IGNORE INTO form_templates (template_name, category, description, fields, settings) VALUES

('Customer Satisfaction Survey', 'survey', 'Measure customer satisfaction',
'[{"type":"radio","label":"How would you rate your overall experience?","name":"rating","required":true,"options":["Excellent","Good","Fair","Poor"]},{"type":"radio","label":"How likely are you to recommend us?","name":"nps","required":true,"options":["Very Likely","Likely","Neutral","Unlikely","Very Unlikely"]},{"type":"textarea","label":"What did we do well?","name":"positive"},{"type":"textarea","label":"What could we improve?","name":"improve"},{"type":"checkbox","label":"May we contact you for follow-up?","name":"contact","value":"yes"}]',
'{"notifications":{"enabled":true}}'),

('Product Feedback Survey', 'survey', 'Gather product feedback',
'[{"type":"text","label":"Product Name","name":"product","required":true},{"type":"radio","label":"How often do you use this product?","name":"frequency","required":true,"options":["Daily","Weekly","Monthly","Rarely"]},{"type":"radio","label":"How satisfied are you with the product?","name":"satisfaction","required":true,"options":["Very Satisfied","Satisfied","Neutral","Dissatisfied","Very Dissatisfied"]},{"type":"checkbox","label":"What features do you use most?","name":"features","options":["Feature A","Feature B","Feature C","Feature D","Feature E"]},{"type":"textarea","label":"What features would you like to see added?","name":"suggestions"}]',
'{"notifications":{"enabled":true}}'),

('Market Research Survey', 'survey', 'Market research questionnaire',
'[{"type":"select","label":"Age Range","name":"age","required":true,"options":["18-24","25-34","35-44","45-54","55-64","65+"]},{"type":"select","label":"Annual Income","name":"income","options":["Under $25k","$25k-$50k","$50k-$75k","$75k-$100k","$100k-$150k","Over $150k"]},{"type":"checkbox","label":"Which brands do you currently use?","name":"brands","options":["Brand A","Brand B","Brand C","Brand D","Brand E","Other"]},{"type":"radio","label":"How do you typically make purchasing decisions?","name":"decision","required":true,"options":["Price","Quality","Brand","Reviews","Recommendations"]},{"type":"textarea","label":"Additional Comments","name":"comments"}]',
'{"notifications":{"enabled":true}}'),

('Employee Satisfaction Survey', 'survey', 'Internal employee survey',
'[{"type":"radio","label":"How satisfied are you with your role?","name":"role_satisfaction","required":true,"options":["Very Satisfied","Satisfied","Neutral","Dissatisfied","Very Dissatisfied"]},{"type":"radio","label":"Do you feel valued at work?","name":"valued","required":true,"options":["Always","Usually","Sometimes","Rarely","Never"]},{"type":"radio","label":"How would you rate work-life balance?","name":"balance","required":true,"options":["Excellent","Good","Fair","Poor"]},{"type":"textarea","label":"What would improve your work experience?","name":"improvements"},{"type":"checkbox","label":"This survey is anonymous","name":"anonymous","value":"yes","disabled":true}]',
'{"notifications":{"enabled":true,"admin_only":true}}'),

('Event Feedback Survey', 'survey', 'Post-event feedback',
'[{"type":"radio","label":"How would you rate the overall event?","name":"overall","required":true,"options":["Excellent","Good","Fair","Poor"]},{"type":"radio","label":"Was the content relevant to you?","name":"relevance","required":true,"options":["Very Relevant","Somewhat Relevant","Not Very Relevant","Not Relevant"]},{"type":"radio","label":"How was the venue?","name":"venue","required":true,"options":["Excellent","Good","Fair","Poor"]},{"type":"textarea","label":"What was your favorite part?","name":"favorite"},{"type":"textarea","label":"What could be improved?","name":"improve"},{"type":"checkbox","label":"Would you attend future events?","name":"future","value":"yes"}]',
'{"notifications":{"enabled":true}}'),

('Website Usability Survey', 'survey', 'Website user experience survey',
'[{"type":"radio","label":"How easy is it to navigate our website?","name":"navigation","required":true,"options":["Very Easy","Easy","Neutral","Difficult","Very Difficult"]},{"type":"radio","label":"How quickly did you find what you were looking for?","name":"speed","required":true,"options":["Immediately","Within 1 minute","Within 5 minutes","Took too long","Did not find it"]},{"type":"radio","label":"How visually appealing is the website?","name":"design","required":true,"options":["Very Appealing","Appealing","Neutral","Unappealing","Very Unappealing"]},{"type":"textarea","label":"What features are you missing?","name":"missing"},{"type":"textarea","label":"Any other suggestions?","name":"suggestions"}]',
'{"notifications":{"enabled":true}}'),

('Training Evaluation', 'survey', 'Post-training evaluation form',
'[{"type":"text","label":"Training Title","name":"training","required":true},{"type":"text","label":"Instructor Name","name":"instructor"},{"type":"radio","label":"Overall training quality","name":"quality","required":true,"options":["Excellent","Good","Fair","Poor"]},{"type":"radio","label":"Content was relevant to my job","name":"relevance","required":true,"options":["Strongly Agree","Agree","Neutral","Disagree","Strongly Disagree"]},{"type":"radio","label":"Instructor was knowledgeable","name":"instructor_rating","required":true,"options":["Strongly Agree","Agree","Neutral","Disagree","Strongly Disagree"]},{"type":"textarea","label":"Key takeaways","name":"takeaways"},{"type":"textarea","label":"Suggestions for improvement","name":"improvements"}]',
'{"notifications":{"enabled":true}}'),

('Service Quality Survey', 'survey', 'Rate our service quality',
'[{"type":"radio","label":"How was the speed of service?","name":"speed","required":true,"options":["Excellent","Good","Fair","Poor"]},{"type":"radio","label":"How was the quality of service?","name":"quality","required":true,"options":["Excellent","Good","Fair","Poor"]},{"type":"radio","label":"How knowledgeable was our staff?","name":"knowledge","required":true,"options":["Excellent","Good","Fair","Poor"]},{"type":"radio","label":"How friendly was our staff?","name":"friendliness","required":true,"options":["Excellent","Good","Fair","Poor"]},{"type":"textarea","label":"Additional comments","name":"comments"}]',
'{"notifications":{"enabled":true}}'),

('Brand Awareness Survey', 'survey', 'Brand perception survey',
'[{"type":"radio","label":"How familiar are you with our brand?","name":"familiarity","required":true,"options":["Very Familiar","Familiar","Somewhat Familiar","Not Familiar"]},{"type":"checkbox","label":"Where have you seen our brand?","name":"channels","options":["Social Media","TV/Radio","Online Ads","Word of Mouth","Events","News/Articles","Never seen"]},{"type":"radio","label":"What is your perception of our brand?","name":"perception","required":true,"options":["Very Positive","Positive","Neutral","Negative","Very Negative"]},{"type":"textarea","label":"What words come to mind about our brand?","name":"words"},{"type":"radio","label":"Would you consider using our services?","name":"consideration","required":true,"options":["Definitely","Probably","Maybe","Probably Not","Definitely Not"]}]',
'{"notifications":{"enabled":true}}'),

('Pre-Purchase Survey', 'survey', 'Understand customer before purchase',
'[{"type":"select","label":"What brings you here today?","name":"intent","required":true,"options":["Ready to Buy","Just Browsing","Researching Options","Price Comparison","Specific Question"]},{"type":"select","label":"How soon are you looking to purchase?","name":"timeframe","options":["Today","This Week","This Month","In 3+ Months","Just Looking"]},{"type":"checkbox","label":"What factors are most important?","name":"factors","options":["Price","Quality","Brand Reputation","Customer Reviews","Warranty","Features"]},{"type":"radio","label":"Have you used similar products before?","name":"experience","required":true,"options":["Yes, regularly","Yes, occasionally","No, this is my first time"]},{"type":"textarea","label":"Any questions before you buy?","name":"questions"}]',
'{"notifications":{"enabled":true}}'),

('Exit Survey', 'survey', 'Why are you leaving?',
'[{"type":"radio","label":"Why are you canceling?","name":"reason","required":true,"options":["Too Expensive","Not Using It","Found Better Alternative","Technical Issues","Poor Customer Service","Other"]},{"type":"radio","label":"How long were you a customer?","name":"duration","required":true,"options":["Less than 1 month","1-3 months","3-6 months","6-12 months","Over 1 year"]},{"type":"textarea","label":"What could have kept you as a customer?","name":"retention"},{"type":"radio","label":"Would you consider returning in the future?","name":"return","options":["Yes","Maybe","No"]},{"type":"textarea","label":"Additional feedback","name":"feedback"}]',
'{"notifications":{"enabled":true,"priority":"high"}}'),

('Poll Template', 'survey', 'Quick single-question poll',
'[{"type":"radio","label":"Your Question Here","name":"poll_question","required":true,"options":["Option 1","Option 2","Option 3","Option 4"]},{"type":"text","label":"Email (Optional)","name":"email"}]',
'{"notifications":{"enabled":false}}');
