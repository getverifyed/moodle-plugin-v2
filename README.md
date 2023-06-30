# VerifyEd Moodle Plugin
The official VerifyEd Moodle plugin, allowing automated course creation and certificate issuance.

# Setup Instructions
This Moodle plugin has been developed for Moodle 4.2, however, it should work for some previous and future platform versions.

In order to use this plugin, you'll need to have your VerifyEd API key ready to save during the configuration steps. You can find your API key by navigating to the 'Automation' tab of your institutional dashboard: https://app.verifyed.io/?tab=3

Then, follow these steps:
1. Log into your Moodle instance as an admin
2. Navigate to 'Site Administration'
3. Click 'Plugins'
4. Click 'Install Plugins'
5. Create a Zipped version of the folder named 'mod_verifyed' from this repository
6. Upload the zipped folder
7. Save the plugin as 'mod_verifyed'
8. Click 'Upload this file'
9. Click 'Install plugin from the ZIP file'
10. Continue through the next few screens
11. Paste the VerifyEd API key into the settings screen when prompted for it

Initial setup is then complete, and the plugin is installed and ready to be added to course instances.

# Using the plugin
The plugin has two key features:
1. It will automatically create a new course in VerifyEd when you install the plugin for a course instance, and,
2. Depending on your rules, learners can claim their digital certificates automatically when they meet the appropriate completion level.

To use the plugin, do this:
1. Navigate to 'My courses'
2. Choose a course you want to add the plugin to
3. Turn on edit mode
4. Under the 'General' section of the course, click 'Add an activity or resource'
5. Choose 'VerifyEd' from the list
6. Choose the template you want to use for the automatic issuances (you can find your templates or create new ones here: https://app.verifyed.io/templates)
7. Name the plugin instance (e.g. 'Digital certificate')
8. You can optionally choose to restrict access to the plugin based on grades etc. This will mean the plugin is locked until learners reach a certain point in their educational journey.
9. Click 'Save and return to course'

That's it. The course will be created in VerifyEd, and when your learners reach a certain point in their learning journey, they'll be able to click the plugin to claim their digital certificate.
