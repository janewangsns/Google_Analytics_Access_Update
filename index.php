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
$client->addScope(Google_Service_Analytics::ANALYTICS_MANAGE_USERS);
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);

//if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
//    unset($_SESSION['access_token']);
//    session_destroy();
//}

// If the user has already authorized this app then get an access token
// else redirect to ask the user to authorize access to Google Analytics.
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    // Set the access token on the client.
    $client->setAccessToken($_SESSION['access_token']);

    // Create an authorized analytics service object.
    $analytics = new Google_Service_Analytics($client);

//    include __DIR__ . '/profile.php';
//    die();
    // Fixed Params
    $valid_emails = array(
        'Aasif' => 'aasif.a@sitesnstores.com.au',
        'Ben' => 'ben@sitesnstores.com.au',
        'BT' => 'brendan.t@sitesnstores.com.au',
        'Gus' => 'gus.r@sitesnstores.com.au',
        'James' => 'james.m@sitesnstores.com.au',
        'Jay' => 'jay@sitesnstores.com.au',
        'Joe' => 'joe@sitesnstores.com.au',
        'Justin' => 'justin.o@sitesnstores.com.au',
        'Tim' => 'tim@sitesnstores.com.au'
    );

    if (($handle = fopen("Access_Update.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // View details
            $accountId = $data[1];
            $webPropertyId = $data[2];
            $profileId = $data[3];

            // Ready-to-assign user details
            $email = $valid_emails[$data[0]];
            $user_exist = false;

            // Ready-to-remove users' details
            $remove_emails = $valid_emails;
            unset($remove_emails[$data[0]]);

            // Get current user list
            $profile = getUsers($analytics, $accountId, $webPropertyId, $profileId);
            foreach ($profile->getItems() as $profile_item) {
                $userEmail = $profile_item->getUserRef()->email;
                if (in_array($userEmail, $remove_emails)){
                    $userId = $profile_item->getUserRef()->id;
                    removeUser($analytics, $accountId, $webPropertyId, $profileId, $userId);
                }
                if ($userEmail == $email){
                    $user_exist = true;
                }
            }

            if ($user_exist !== true){
                addUser($analytics, $accountId, $webPropertyId, $profileId, $email);
            }
        }
        fclose($handle);
    }
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
    return $profileUserlinks;
}

function removeUser($analytics, $accountId, $webPropertyId, $profileId, $userId){
    try {
        $analytics->management_profileUserLinks->delete($accountId, $webPropertyId, $profileId, $profileId.':'.$userId);
    } catch (apiServiceException $e) {
        print 'There was an Analytics API service error '
            . $e->getCode() . ':' . $e->getMessage();

    } catch (apiException $e) {
        print 'There was a general API error '
            . $e->getCode() . ':' . $e->getMessage();
    }
}

function addUser($analytics, $accountId, $webPropertyId, $profileId, $userEmail){
    // Create the user reference.
    $userRef = new Google_Service_Analytics_UserRef();
    $userRef->setEmail($userEmail);

    // Create the permissions object.
    $permissions = new Google_Service_Analytics_EntityUserLinkPermissions();
    $permissions->setLocal(array('READ_AND_ANALYZE'));

    // Create the view (profile) user link.
    $link = new Google_Service_Analytics_EntityUserLink();
    $link->setPermissions($permissions);
    $link->setUserRef($userRef);

    // This request creates a new View (Profile) User Link.
    try {
        $analytics->management_profileUserLinks->insert($accountId, $webPropertyId, $profileId, $link);
    } catch (apiServiceException $e) {
        print 'There was an Analytics API service error '
            . $e->getCode() . ':' . $e->getMessage();

    } catch (apiException $e) {
        print 'There was a general API error '
            . $e->getCode() . ':' . $e->getMessage();
    }
}