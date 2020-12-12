<?php

/**
 * RESULTS.js
 * Nous avons besoin d'afficher tous les posts et toutes les fiches (si all cliqué)
 * Pour cela, nous avons besoins des :
 * Id des categories parentes et enfants
 * au depart de ces id nous allons charger tous les posts
 * et egalement checker si des fiches bottins existent pour les ajouter
 *
 */

use AcMarche\Bottin\Repository\BottinRepository; //import class


// function ca_tout($request)
// {
//     // rechercher toutes les fiches du bottin

//     $catParent = null;
//     if (isset($request['catparent'])) { // checker si c'est bien ca
//         $catParent = $request['catparent'];
//     }

//     $ids = post_fiches_categories_ids($catParent);
//     $idsBottin = $ids['bottin'];
//     $idsWp = $ids['wp'];

//     $bottinRepository = new BottinRepository();
//     $fiches           = [];

//     foreach ($idsBottin as $id) {
//         $fiches[] = $bottinRepository->getFichesByCategory($id);
//     }
//     /**
//      * je nettoie et format pour que ca soit commun à post
//      */
//     foreach ($fiches as $fiche) {
//         $data[$fiche->id]['id'] = $fiche->id;
//         $data[$fiche->id]['name'] = $fiche->societe;
//         $data[$fiche->id]['url'] = 'https://new.marche.be/zezez' . $fiche->id;
//     }
//     /**
//      * rechercher tous les posts
//      **/
//     $posts =  get_posts(['cat_IN' => $idsWp]);
//     $all = array_merge($data, $posts);

//     return rest_ensure_response($all);
// }


/**
 * En partant de l'id parent, je vais chercher ses enfants et j'enregistre tous les ids parent compris
 * et pour chaque enfant je regarde si il dipose d'une référence au bottin
 * 
 */
function post_fiches_categories_ids($catParent)
{
    $args     = ['parent' => $catParent, 'hide_empty' => false];
    $children = get_categories($args);
    $ids = ['wp' => [], 'bottin' => []]; //multidimensional array
    $ids['wp'][] = $catParent; //j'enregiste le parent
    /**
     * ici je vérifie si le parent a lui meme une référence au bottin
     */
    $categoryBottinId = get_term_meta($catParent, \BottinCategoryMetaBox::KEY_NAME, true); //ici on recupere l'id du BOTTIN c'est encodé en méta donné de la categorie
    if ($categoryBottinId) {
        $ids['bottin'][] = $categoryBottinId;
    }

    /**
     * je parcours chaque enfant pour voir s'il a une référence du bottin en méta donnée
     * si oui je l'enregistre
     */
    foreach ($children as $cat) {
        $ids['wp'][] = $cat->cat_ID; // il est important de checker si nous avons des posts dans chaque categorie (peut contenir des posts et fiches)
        $categoryBottinId = get_term_meta($cat->cat_ID, \BottinCategoryMetaBox::KEY_NAME, true); //ici on recupere l'id du BOTTIN c'est encodé en méta donné de la categorie
        if ($categoryBottinId) {
            $ids['bottin'][] = $categoryBottinId;
        }
    }

    return $ids; // ensemble de wp et bottin
}

add_action('rest_api_init', function () {
    register_rest_route('ca/v1', 'fiches/(?P<catParent>.*+)', [
        'methods' => 'GET',
        'callback' => 'ca_tout',

    ]);
});
