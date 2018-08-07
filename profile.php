<?php
/**
 * Created by PhpStorm.
 * User: Jane Wang
 * Date: 2/08/2018
 * Time: 5:17 PM
 */
$account_ids = array(
    '46650000',
    '30162635',
    '57089560',
    '90572048',
    '71410660',
    '42132822',
    '50285162',
    '44563313',
    '45222542',
    '63735131',
    '16450739',
    '31390804',
    '53384256',
    '25616269',
    '47564724',
    '25443403',
    '31506967',
    '46141295',
    '36639712',
    '44857734'
);
echo "<table>";
foreach ($account_ids as $account_id){
    try {
        $properties = $analytics->management_webproperties
            ->listManagementWebproperties($account_id);

    } catch (apiServiceException $e) {
        print 'There was an Analytics API service error '
            . $e->getCode() . ':' . $e->getMessage();

    } catch (apiException $e) {
        print 'There was a general API error '
            . $e->getCode() . ':' . $e->getMessage();
    }


    foreach ($properties->getItems() as $property) {
        $property_id = $property->getId();
        $property_name = $property->getName();
        $profiles = $analytics->management_profiles
            ->listManagementProfiles($account_id, $property_id);
        foreach ($profiles->getItems() as $profile) {
            echo "<tr><td>".$account_id."</td><td>".$property_id."</td>";
            echo "<td>".$profile->getId()."</td>";
            echo "<td>".$property_name."</td></tr>";
        }
    }
}
echo "</table>";


