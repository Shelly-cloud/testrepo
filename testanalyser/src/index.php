<?php
$VENDOR_DIRECTORY_PATH =  __dir__ . "/../vendor";

require_once $VENDOR_DIRECTORY_PATH . "/repo_monitor/src/app/pull_commits.php";

$githubUsername = "shelly-cloud";
$repoName = "testrepo";
$githubAuthToken = "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";
$pageSize = 100; //Total number of commits to be fetched from Github in one API call. Max value is 100 as per the limitaions from Github itself
$FETCH_TOTAL_COMMITS_CAP = 1000; //The total number of commits to be fetched for the repo. The process will go upto these number of commits in backward by date.

$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'testanalyser';

$fetchCommitsResult = fetchRepoWithCommits($dbHost,$dbUser,$dbPassword,$dbName,$githubUsername, $repoName, $pageSize, $FETCH_TOTAL_COMMITS_CAP, $githubAuthToken);
print_r($fetchCommitsResult);
