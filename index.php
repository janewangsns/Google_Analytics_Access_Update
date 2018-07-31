<?php
/**
 * Created by PhpStorm.
 * User: Jane Wang
 * Date: 30/07/2018
 * Time: 2:27 PM
 */
// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';

// Start a session to persist credentials.
session_start();

// Create the client object and set the authorization configuration
// from the client_secretes.json you downloaded from the developer console.
$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secrets.json');
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);

// If the user has already authorized this app then get an access token
// else redirect to ask the user to authorize access to Google Analytics.
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    // Set the access token on the client.
    $client->setAccessToken($_SESSION['access_token']);

    // Create an authorized analytics service object.
    $analytics = new Google_Service_Analytics($client);

    $accountId = '91314234';
    $webPropertyId = 'UA-91314234-37';
    $profileId = '155131539';

    $profile = getUsers($analytics, $accountId, $webPropertyId, $profileId);
//    echo "<pre>";
//    print_r($profile);
//    echo "die here";
    die();
    // Get the first view (profile) id for the authorized user.
    $profile = getFirstProfileId($analytics);

    // Get the results from the Core Reporting API and print the results.
    $results = getResults($analytics, $profile);
    printResults($results);
} else {
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/GoogleAnalytics_AccessUpdate/oauth2callback.php';
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

function getUsers($analytics, $accountId, $webPropertyId, $profileId){
    try {
        $profileUserlinks = $analytics->management_profileUserLinks
            ->listManagementProfileUserLinks($accountId, $webPropertyId, $profileId);
    } catch (apiServiceException $e) {
        print 'There was an Analytics API service error '
            . $e->getCode() . ':' . $e->getMessage();

    } catch (apiException $e) {
        print 'There was a general API error '
            . $e->getCode() . ':' . $e->getMessage();
    }

    foreach ($profileUserlinks->getItems() as $profileUserLink) {
        $entity = $profileUserLink->getEntity();
        $profileRef = $entity->getProfileRef();
        $userRef = $profileUserLink->getUserRef();
        $permissions = $profileUserLink->getPermissions();

        $html = <<<HTML
<pre>
Profile user link id   = {$profileUserLink->getId()}
Profile user link kind = {$profileUserLink->getKind()}

Profile id   = {$profileRef->getId()}
Profile name = {$profileRef->getName()}
Profile kind = {$profileRef->getKind()}

Permissions local     = {$permissions->getLocal()}
Permissions effective = {$permissions->getEffective()}

User id    = {$userRef->getId()}
User kind  = {$userRef->getKind()}
User email = {$userRef->getEmail()}
</pre>
HTML;
        print $html;
    }
    return $profileUserlinks;
}


function getFirstProfileId($analytics) {
    // Get the user's first view (profile) ID.

    // Get the list of accounts for the authorized user.
    $accounts = $analytics->management_accounts->listManagementAccounts();

    if (count($accounts->getItems()) > 0) {
        $items = $accounts->getItems();
        $firstAccountId = $items[0]->getId();

        // Get the list of properties for the authorized user.
        $properties = $analytics->management_webproperties
            ->listManagementWebproperties($firstAccountId);

        if (count($properties->getItems()) > 0) {
            $items = $properties->getItems();
            $firstPropertyId = $items[0]->getId();

            // Get the list of views (profiles) for the authorized user.
            $profiles = $analytics->management_profiles
                ->listManagementProfiles($firstAccountId, $firstPropertyId);

            if (count($profiles->getItems()) > 0) {
                $items = $profiles->getItems();

                // Return the first view (profile) ID.
                return $items[0]->getId();

            } else {
                throw new Exception('No views (profiles) found for this user.');
            }
        } else {
            throw new Exception('No properties found for this user.');
        }
    } else {
        throw new Exception('No accounts found for this user.');
    }
}

function getResults($analytics, $profileId) {
    // Calls the Core Reporting API and queries for the number of sessions
    // for the last seven days.
    return $analytics->data_ga->get(
        'ga:' . $profileId,
        '7daysAgo',
        'today',
        'ga:sessions');
}

function printResults($results) {
    // Parses the response from the Core Reporting API and prints
    // the profile name and total sessions.
    if (count($results->getRows()) > 0) {

        // Get the profile name.
        $profileName = $results->getProfileInfo()->getProfileName();

        // Get the entry for the first entry in the first row.
        $rows = $results->getRows();
        $sessions = $rows[0][0];

        // Print the results.
        print "<p>First view (profile) found: $profileName</p>";
        print "<p>Total sessions: $sessions</p>";
    } else {
        print "<p>No results found.</p>";
    }
}