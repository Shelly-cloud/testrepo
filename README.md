Deployment instruction
Create database and import file src/database/db.sql
Change config values in src/config/config.php
Run file php src/cli/pull_commits.php 
The script will start pulling commits for each branch of the repo and pushing it into the database



Useful links
To create Github auth token 
https://github.com/settings/tokens/new

Github API to get commit list
https://docs.github.com/en/rest/commits/commits#list-commits 

A good article about fetching all commits of repo
https://stackoverflow.com/questions/9179828/github-api-retrieve-all-commits-for-all-branches-for-a-repo
