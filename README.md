# catalyst-app

Task for appying for Drupal / PHP Software Developer in Catalyst IT Australia

Candidate : Chen Li

1. Assumptions

   1.1 Database name is not provided in the requirements. Assumption is made here by using a fixed database name: catalyst_users

   1.2 Assuming all database login credentials need to be provided together to establish the connection.

   1.3 While the task description mentioned using 'user_upload.php' for the PHP script, I've created 'user_upload.php' just for command line options.
   The core code is saved in 'data_processor.php' for better organization and maintainability.

2. Library for project

In order to read excel file, PhpSpreadsheet has been introduced to this project.

The composer dependency configuration (composer.json) has been added to the Git repository.
Please install the dependencies using the following command before running the code:

    composer install

3. Command line quick guide

• --file [csv file name] – this is the name of the CSV to be parsed
• --create_table – this will cause the MySQL users table to be built (and no further
• action will be taken)
• --dry_run – this will be used with the --file directive in case we want to run the script but not
insert into the DB. All other functions will be executed, but the database won't be altered
• -u – MySQL username
• -p – MySQL password
• -h – MySQL host
• --help – which will output the above list of directives with details.
