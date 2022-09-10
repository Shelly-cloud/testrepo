[Database setup]
Create database with name 'repo_monitor' and import file src/database/db.sql. You can choose some other name also and use the same in config.php
Change config values in src/config/config.php for databae credentials.

[Run script to pull data from github]
Run below file with github username and reponame
src/cli/php pull_commits.php shelly-cloud testrepo
The script will start pulling commits for each branch of the repo and pushing it into the database.
The above script will ask for Github auth token which can be generated using below user. You need to login to Github to generate auth token.
https://github.com/settings/tokens/new

[List commits for all the branches of a repo and date range]
To list commits for a given repository and date range.
Run below file with github user, reponame, start date, end date
php src/cli/php list_commits.php shelly-cloud testrepo 2019-08-14 2022-08-30


Useful links
To create Github auth token 
https://github.com/settings/tokens/new

Github API to get commit list
https://docs.github.com/en/rest/commits/commits#list-commits 

A good article about fetching all commits of repo
https://stackoverflow.com/questions/9179828/github-api-retrieve-all-commits-for-all-branches-for-a-repo

How To Configure PHP OpenSSL Module On Windows. In case if its not already configured on server. 
https://php.tutorials24x7.com/blog/how-to-configure-php-openssl-module-on-windows