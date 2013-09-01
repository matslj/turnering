<?php

// ===========================================================================================
//
// config_nav.php
//
// Navigation specific configurations.
//

$menuNavBar = Array (
        'Hem'           => '?p=home',
        'Matchups' 	    => '?p=matchup',
        'Poängtavla' 	=> '?p=scoreboard',
);
define('MENU_NAVBAR', serialize($menuNavBar));

$menuNavBarForAdmin = Array (
        'Hem'           => '?p=home',
        'Matchups' 	    => '?p=matchup',
        'Poängtavla' 	=> '?p=scoreboard',
        'Konfiguration' => '?p=tournament',
        //'Bildarkiv' 	=> '?p=archive',
        'Deltagare'     => '?p=admin_anvandare',
        //'Bildintresse' 	=> '?p=userinterest',
        //'Admin'         => '?p=admin',
    	//'Om' 		=> '?p=about',
);
define('MENU_NAVBAR_FOR_ADMIN', serialize($menuNavBarForAdmin));

// Admin menu - side menu (column menu) but it can of course be used in other ways
$adminMenuNavBar = Array (
        'Användare'         => '?p=admin_anvandare',
        'Kategorier'        => '?p=admin_folders',
        'Bildarkiv'         => '?p=admin_archive',
        'Koppla användare'  => '?p=admin_manager',
);
define('ADMIN_MENU_NAVBAR',      serialize($adminMenuNavBar));

// Some constants (should not be here)

$selectableArmies = Array (
    'Vampire Counts' => 'vampirecounts',
    'Ogre Kingdoms' => 'ogrekingdoms',
    'Skaven' => 'skaven',
    'Orcs and Goblins' => 'orcsandgoblins',
    'The Empire' => 'empire',
    'Bretonnia' => 'bretonnia',
    'Tomb Kings' => 'tombkings',
    'Warriors of Chaos' => 'warriorsofchaos',
    'Beasts of Chaos' => 'beastsofchaos',
    'Daemons of Chaos' => 'daemonsofchaos',
    'Dark elves' => 'darkelves',
    'Dwarfs' => 'dwarfs',
    'High Elves' => 'highelves',
    'Wood Elves' => 'woodelves',
    'Lizardmen' => 'lizardmen',
);
define('SELECTABLE_ARMIES', serialize($selectableArmies));
?>