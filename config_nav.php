<?php

// ===========================================================================================
//
// config_nav.php
//
// Navigation specific configurations.
//

// # in the menu below means that the user has to be logged in for this item to show up.
$menuNavBar = Array (
        'Hem'              => '?p=home',
        '#Skapa turnering'    => '?p=admin_tournament',
);
define('MENU_NAVBAR', serialize($menuNavBar));

$menuNavBarForAdmin = Array (
        'Hem'           => '?p=home',
        'Konfiguration' => '?p=admin_tournament',
        'Deltagare'     => '?p=admin_anvandare',
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

$subMenuNavBar = Array (
        'Hem - vald turnering' => '?p=tupc',
        'Matchups' 	    => '?p=matchup',
        'Resultatlista' => '?p=scoreboard',
);
define('SUB_MENU_NAVBAR',      serialize($subMenuNavBar));

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

$selectableTieBreakers = Array (
    'Inbördes möte' => 'internalwinner',
    'Flest vunna' => 'mostwon',
    'Original poäng' => 'orgscore',
);
define('SELECTABLE_TIE_BREAKERS', serialize($selectableTieBreakers));
?>