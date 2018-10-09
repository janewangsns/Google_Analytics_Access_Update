<?php
/**
 * Created by PhpStorm.
 * User: Jane Wang
 * Date: 30/07/2018
 * Time: 2:13 PM
 */
// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';

// Start a session to persist credentials.
session_start();

// Create the client object and set the authorization configuration
// from the client_secrets.json you downloaded from the Developers Console.
$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secrets.json');
$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php');
$client->addScope(array(Google_Service_Analytics::ANALYTICS_MANAGE_USERS, Google_Service_Analytics::ANALYTICS));

// Handle authorization flow from the server.
if (! isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php';
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}