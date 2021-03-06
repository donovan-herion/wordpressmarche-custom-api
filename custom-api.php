<?php

/**
 * Plugin Name: Custom API
 * Description: This is a customizable api
 * Version: 1.0
 */

// This plugin adds a custom endpoint that returns all posts and fiches
// The posts and fiches are formatted the same way
// The posts and fiches have a common react_category_filter property used in React for filtering purposes

use AcMarche\Bottin\Repository\BottinRepository; //import class that returns an array of fiches based on id
use AcMarche\Bottin\Bottin;
use AcMarche\Theme\Inc\Router;
use AcMarche\Pivot\Repository\HadesRepository;




function ca_all($parameter)
{

    $catParent = null;
    if (isset($parameter['catParent'])) {
        $catParent = $parameter['catParent'];
    }

    $ids = wp_fiches_categories_id($catParent);
    $idsWp = $ids['wp'];
    $idsBottin = $ids['bottin'];
    $idsAssociationBottinWp = $ids['association_bottin_wp'];

    // retrieve all fiches and add the wp category ids to them
    $bottinRepository = new BottinRepository();
    $fiches           = [];

    foreach ($idsBottin as $id) {
        $array_of_fiches[] = $bottinRepository->getFichesByCategory($id); //RETURNS AN ARRAY OF FICHES
        $i = 0;
        foreach ($array_of_fiches as $single_fiches) {
            foreach ($single_fiches as $sf) {
                $sf->react_category_filter = []; //if not initialised adds all ids from the loop
                $sf->react_category_filter[] = $idsAssociationBottinWp[$i];
            }
            $fiches = array_merge($fiches, $single_fiches);
            $i++;
        }
    }
    $data = [];
    // Formats all fiches to fit front end requirements
    foreach ($fiches as $fiche) {
        $data[$fiche->id]['ID'] = $fiche->id;
        $data[$fiche->id]['post_title'] = $fiche->societe;
        $data[$fiche->id]['react_category_filter'] = $fiche->react_category_filter;
        $data[$fiche->id]['excerpt']    = Bottin::getExcerpt($fiche);
        $data[$fiche->id]['link']  = Router::getUrlFicheBottin($fiche);
    }

    //retrieves all posts and add the wp category ids to them
    $query = new WP_Query(['category__in' => $idsWp]);
    $posts =  $query->get_posts();
    foreach ($posts as $post) {
        $post->react_category_filter = wp_get_post_categories($post->ID);
    }

    //combines formatted fiches (data) and posts
    $all = array_merge($data, $posts);

    // returns all posts and fiches with their respective wp category 
    return rest_ensure_response($all);
}


// Based on MainCategoyId returns an associative and multidimensional array
// ids['wp'] = MainCategoryId and ChildrenCagegoryIds used to retrieve posts in ca_all()
// ids['bottin'] = BottinCategoryIds used to retrieve fiches in ca_all()
// ids['association_wp_bottin'] = the wp category is pushed in this array when a bottin id is added for filtering purposes 
function wp_fiches_categories_id($catParent)
{
    $ids = ['wp' => [], 'bottin' => [], 'association_bottin_wp' => []]; //initialising the associative and multidimensional array

    $args     = ['parent' => $catParent, 'hide_empty' => false];
    $children_cat = get_categories($args);

    $ids['wp'][] = $catParent; //adds the main category to the list of ids
    foreach ($children_cat as $cat) {
        $ids['wp'][] = $cat->cat_ID; //adds the children from main category to the list of ids
        $categoryBottinId = get_term_meta($cat->cat_ID, \BottinCategoryMetaBox::KEY_NAME, true); //checks if meta bottinID metadata contains and ID
        if ($categoryBottinId) {
            $ids['bottin'][] = $categoryBottinId;
            $ids['association_bottin_wp'][] = $cat->cat_ID;
        }
    }
    // We also need to check if the main_cat has a bottin ID
    $categoryBottinId = get_term_meta($catParent, \BottinCategoryMetaBox::KEY_NAME, true); //ici on recupere l'id du BOTTIN c'est encodé en méta donné de la categorie
    if ($categoryBottinId) {
        $ids['bottin'][] = $categoryBottinId;
    }

    return $ids; //wp/bottin/association_wp_bottin ids
}
if (!is_admin()) {
    add_action('rest_api_init', function () {
        register_rest_route('ca/v1', 'all/(?P<catParent>.*+)', [
            'methods' => 'GET',
            'callback' => 'ca_all',

        ]);
    });
}
// This plugin also adds a custom endpoint that returns all events from HADES
function ca_events()
{
    $hadesRepository = new HadesRepository();
    $events          = $hadesRepository->getEvents();
    return rest_ensure_response($events);
}

if (!is_admin()) {
    add_action('rest_api_init', function () {
        register_rest_route('ca/v1', 'events', [
            'methods' => 'GET',
            'callback' => 'ca_events',

        ]);
    });
}

// This plugin returns the societe and the id of all companies in the bottin to retrieve them in the gutenberg block
function ca_bottinSocieteId()
{
    $bottinRepository = new BottinRepository();
    $allfiches           = $bottinRepository->getFiches();
    $fichesSocieteId = [];

    foreach ($allfiches as $fiche) {
        $fichesSociete = $fiche['societe'];
        $fichesId      = $fiche['id'];

        $formattedFiche['societe'] = $fichesSociete;
        $formattedFiche['id'] = $fichesId;

        $fichesSocieteId[] = $formattedFiche;
    }

    return rest_ensure_response($fichesSocieteId);
}


add_action('rest_api_init', function () {
    register_rest_route('ca/v1', 'bottinsocieteid', [
        'methods' => 'GET',
        'callback' => 'ca_bottinSocieteId',

    ]);
});

// This plugin also adds a custom endpoint that returns all sheets of the bottin based on their id
function ca_bottin($parameter)
{
    $bottinRepository = new BottinRepository();
    $fiches           = $bottinRepository->getFicheById($parameter['ficheId']);

    return rest_ensure_response($fiches);
}


add_action('rest_api_init', function () {
    register_rest_route('ca/v1', 'bottin/(?P<ficheId>.*+)', [
        'methods' => 'GET',
        'callback' => 'ca_bottin',

    ]);
});

// This plugin also adds a custom endpoint that returns all categories of the bottin
function ca_bottinAllCategories()
{
    $bottinRepository = new BottinRepository();
    $allCategories    = $bottinRepository->getAllCategories();

    return rest_ensure_response($allCategories);
}


add_action('rest_api_init', function () {
    register_rest_route('ca/v1', 'bottinAllCategories', [
        'methods' => 'GET',
        'callback' => 'ca_bottinAllCategories',

    ]);
});


// This plugin also returns a data object that is used for the dynamic map
function ca_map($parameter)
{
    $bottinRepository = new BottinRepository();
    $fiches           = $bottinRepository->getFichesByCategories([$parameter["CatId"]]);

    return rest_ensure_response($fiches);
}


add_action('rest_api_init', function () {
    register_rest_route('ca/v1', 'map/(?P<CatId>.*+)', [
        'methods' => 'GET',
        'callback' => 'ca_map',

    ]);
});
