<?php

require_once 'email.civix.php';

use CRM_Email_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function email_civicrm_config(&$config): void {
  _email_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function email_civicrm_install(): void {
  _email_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function email_civicrm_enable(): void {
  _email_civix_civicrm_enable();
}

function email_civicrm_configure($array_contditjaar, $ditjaar_array = NULL, $array_partditjaar = NULL, $datum_belangstelling = NULL) {

// M61: CHANGED DITJAAR_ARRAY = NULL (added the = NULL)

    $extdebug               = 0;
    $apidebug               = FALSE;
    $extwrite               = 1;

    $firstname              = $array_contditjaar['first_name'] ?? NULL;
    $lastname               = $array_contditjaar['last_name']  ?? NULL;

    if (empty($firstname) && empty($lastname)) {
        wachthond($extdebug, 1, "### ABORT: Poging tot configureren van contact zonder naam.", "[SAFETY]");
        return; // Stop de executie zodat er geen database mutaties plaatsvinden
    }    

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,1, "### EMAIL - CONFIGURE EMAILADRESSES & NOTIFICATIONS",           "[START]");
    wachthond($extdebug,2, "########################################################################");

    if ( !is_array($array_contditjaar) ) {

        wachthond($extdebug,1, "array_contditjaar",     $array_contditjaar);
        wachthond($extdebug,1, "ditjaar_array",         $ditjaar_array);
        wachthond($extdebug,1, "array_partditjaar",     $array_partditjaar);

        wachthond($extdebug,1, 'ENAIL_CIVICRM_CONFIGURE', "[SKIP]");        

        return;
    }

    wachthond($extdebug,4, "array_contditjaar",     $array_contditjaar);

    $today_datetime         = date("Y-m-d H:i:s");
    $today_kampjaar         = Civi::cache()->get('cache_today_kampjaar')    ?? NULL;
    wachthond($extdebug,4, 'today_kampjaar',            $today_kampjaar);

    wachthond($extdebug,4, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 0.1 GET VALUES FROM ARRAY",                 "array_contditjaar");
    wachthond($extdebug,2, "########################################################################");

    $contact_id             = $array_contditjaar['contact_id']              ?? NULL;
    $firstname              = $array_contditjaar['first_name']              ?? NULL;
    $lastname               = $array_contditjaar['last_name']               ?? NULL;
    $displayname            = $array_contditjaar['displayname']             ?? NULL;
    $user_name              = $array_contditjaar['crm_drupalnaam']          ?? NULL;
    $birth_date             = $array_contditjaar['birth_date']              ?? NULL;
    $privacy_voorkeuren     = $array_contditjaar['privacy_voorkeuren']      ?? NULL;

    wachthond($extdebug,3, 'contact_id',                $contact_id);
    wachthond($extdebug,3, 'firstname',                 $firstname);
    wachthond($extdebug,3, 'lastname',                  $lastname);
    wachthond($extdebug,3, 'displayname',               $displayname);
    wachthond($extdebug,3, 'user_name',                 $user_name);
    wachthond($extdebug,3, 'birth_date',                $birth_date);
    wachthond($extdebug,3, 'privacy_voorkeuren',        $privacy_voorkeuren);

    $ditjaar_part_functie   = $array_partditjaar['part_functie']            ?? NULL;
    wachthond($extdebug,3, 'ditjaar_functie',                       $ditjaar_part_functie);

    if ($firstname == 'Hoofdleiding' AND in_array($lastname, array("Kinderkamp1","Kinderkamp2","Brugkamp1","Brugkamp2","Tienerkamp1","Tienerkamp2","Jeugdkamp1","Jeugdkamp2","Topkamp"))) {
        $hoofdleiding_mailboxaccount = 1;
        wachthond($extdebug,3, 'hoofdleiding_mailboxaccount',       $hoofdleiding_mailboxaccount);
    }

    if (in_array($ditjaar_part_functie, array('hoofdleiding', 'kernteamlid', 'kampstaf', 'bestuurslid')) OR $hoofdleiding_mailboxaccount == 1) {

        $cont_notificatie_deel  = $array_contditjaar['cont_notificatie_deel']   ?? NULL;
        $cont_notificatie_leid  = $array_contditjaar['cont_notificatie_leid']   ?? NULL;
        $cont_notificatie_kamp  = $array_contditjaar['cont_notificatie_kamp']   ?? NULL;
        $cont_notificatie_staf  = $array_contditjaar['cont_notificatie_staf']   ?? NULL;

        wachthond($extdebug,3, 'cont_notificatie_deel',     $cont_notificatie_deel);
        wachthond($extdebug,3, 'cont_notificatie_leid',     $cont_notificatie_leid);
        wachthond($extdebug,3, 'cont_notificatie_kamp',     $cont_notificatie_kamp);
        wachthond($extdebug,3, 'cont_notificatie_staf',     $cont_notificatie_staf);
    }

    ##########################################################################################
    // M61: RETREIVE LEEFTIJD NEXT KAMP (ZOU OOK IN ARRAY_CONTACT KUNNEN)
    ##########################################################################################  

    $leeftijd_vantoday  = leeftijd_civicrm_diff('vandaag', $birth_date, $today_datetime);
    wachthond($extdebug,3, 'leeftijd_vantoday',             $leeftijd_vantoday);
    $leeftijd_vantoday_decimalen  = $leeftijd_vantoday['leeftijd_decimalen']    ?? NULL;
    $leeftijd_vantoday_rondjaren  = $leeftijd_vantoday['leeftijd_rondjaren']    ?? NULL;
    wachthond($extdebug,3, 'leeftijd_vantoday_decimalen',   $leeftijd_vantoday_decimalen);

    ##########################################################################################
    // M61: RETREIVE VALID USERNAME (ZOU OOK IN ARRAY_CONTACT KUNNEN)
    ##########################################################################################  

    $array_username     = drupal_civicrm_username($contact_id);
    $user_name          = $array_username['user_name'];
    wachthond($extdebug,3, 'array_username',                $array_username);
    wachthond($extdebug,3, 'user_name',                     $user_name);

    wachthond($extdebug,3, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 0.2 GET VALUES FROM ARRAY",                     "ditjaar_array");
    wachthond($extdebug,2, "########################################################################");

    $ditjaardeelyes     = $ditjaar_array['diteventdeelyes'];
    $ditjaardeelnot     = $ditjaar_array['diteventdeelnot'];
    $ditjaardeelmss     = $ditjaar_array['diteventdeelmss'];
    $ditjaardeelstf     = $ditjaar_array['diteventdeelstf'];
    $ditjaardeeltst     = $ditjaar_array['diteventdeeltst'];
    $ditjaardeeltxt     = $ditjaar_array['diteventdeeltxt'];

    $ditjaarleidyes     = $ditjaar_array['diteventleidyes'];
    $ditjaarleidnot     = $ditjaar_array['diteventleidnot'];
    $ditjaarleidmss     = $ditjaar_array['diteventleidmss'];
    $ditjaarleidstf     = $ditjaar_array['diteventleidstf'];
    $ditjaarleidtst     = $ditjaar_array['diteventleidtst'];
    $ditjaarleidtxt     = $ditjaar_array['diteventleidtxt'];

    wachthond($extdebug,3, 'ditjaardeelyes',    $ditjaardeelyes);
    wachthond($extdebug,3, 'ditjaardeelmss',    $ditjaardeelmss);
    wachthond($extdebug,3, 'ditjaarleidyes',    $ditjaarleidyes);
    wachthond($extdebug,3, 'ditjaarleidmss',    $ditjaarleidmss);

    wachthond($extdebug,3, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 0.3 GET VALUES FROM ARRAY",                "array_partditjaar",);
    wachthond($extdebug,2, "########################################################################");

    $ditjaar_part_contact_id                = $array_partditjaar['contact_id']                          ?? NULL;
    $ditjaar_part_eventid                   = $array_partditjaar['event_id']                            ?? NULL;
    $ditjaar_part_id                        = $array_partditjaar['id']                                  ?? NULL;
    $ditjaar_part_role_id                   = $array_partditjaar['role_id']                             ?? NULL;
    $ditjaar_part_status_id                 = $array_partditjaar['status_id']                           ?? NULL;
    $ditjaar_part_status_name               = $array_partditjaar['status_name']                         ?? NULL;

    $ditjaar_register_date                  = $array_partditjaar['register_date']                       ?? NULL;
    $ditjaar_event_start                    = $array_partditjaar['event_start_date']                    ?? NULL;
    $ditjaar_event_einde                    = $array_partditjaar['event_end_date']                      ?? NULL;
    $ditjaar_kampjaar                       = $array_partditjaar['part_kampjaar']                       ?? NULL;
    $ditjaar_part_kampweek_nr               = $array_partditjaar['part_kampweek_nr']                    ?? NULL;

    $ditjaar_event_kampnaam                 = $array_partditjaar['kenmerken_kampnaam']                  ?? NULL;
    $ditjaar_event_kampkort                 = $array_partditjaar['kenmerken_kampkort']                  ?? NULL;
    $ditjaar_part_kampnaam                  = $array_partditjaar['part_kampnaam']                       ?? NULL;
    $ditjaar_part_kampkort                  = $array_partditjaar['part_kampkort']                       ?? NULL;

    $ditjaar_part_functie                   = $array_partditjaar['part_functie']                        ?? NULL;
    $ditjaar_part_rol                       = $array_partditjaar['part_rol']                            ?? NULL;
    $ditjaar_leid_welkkamp                  = $array_partditjaar['part_leid_kamp']                      ?? NULL;
    $ditjaar_leid_functie                   = $array_partditjaar['part_leid_functie']                   ?? NULL;

    $ditjaar_part_kamptype_id               = $array_partditjaar['part_kamptype_id']                    ?? NULL;
    $ditjaar_part_kamptype_naam             = $array_partditjaar['part_kamptype_naam']                  ?? NULL;
    $ditjaar_event_type_id                  = $array_partditjaar['event_type_id']                       ?? NULL;
    $ditjaar_event_type_label               = $array_partditjaar['event_type_label']                    ?? NULL;

    $ditjaar_part_notificatie_deel          = $array_partditjaar['part_notificatie_deel']               ?? NULL;
    $ditjaar_part_notificatie_leid          = $array_partditjaar['part_notificatie_leid']               ?? NULL;
    $ditjaar_part_notificatie_kamp          = $array_partditjaar['part_notificatie_kamp']               ?? NULL;
    $ditjaar_part_notificatie_staf          = $array_partditjaar['part_notificatie_staf']               ?? NULL;
    $ditjaar_part_notificatie_priv          = $array_partditjaar['part_notificatie_priv']               ?? NULL;

    wachthond($extdebug,3, 'displayname',                           $displayname);
    wachthond($extdebug,3, 'contact_id',                            $contact_id);
    wachthond($extdebug,3, 'ditjaar_contact_id',                    $ditjaar_part_contact_id);

    wachthond($extdebug,3, 'ditjaar_event_kampnaam',                $ditjaar_event_kampnaam);
    wachthond($extdebug,3, 'ditjaar_event_kampkort',                $ditjaar_event_kampkort);
    wachthond($extdebug,3, 'ditjaar_part_kampnaam',                 $ditjaar_part_kampnaam);
    wachthond($extdebug,3, 'ditjaar_part_kampkort',                 $ditjaar_part_kampkort);

    wachthond($extdebug,3, 'ditjaar_functie',                       $ditjaar_part_functie);
    wachthond($extdebug,3, 'ditjaar_part_rol',                      $ditjaar_part_rol);
    wachthond($extdebug,3, 'ditjaar_leid_welkkamp',                 $ditjaar_leid_welkkamp);
    wachthond($extdebug,3, 'ditjaar_leid_functie',                  $ditjaar_leid_functie);    

    wachthond($extdebug,3, 'ditjaar_event_type_id',                 $ditjaar_event_type_id);
    wachthond($extdebug,3, 'ditjaar_event_type_label',              $ditjaar_event_type_label); 

    wachthond($extdebug,3, 'ditjaar_part_notificatie_deel',         $ditjaar_part_notificatie_deel);
    wachthond($extdebug,3, 'ditjaar_part_notificatie_leid',         $ditjaar_part_notificatie_leid);
    wachthond($extdebug,3, 'ditjaar_part_notificatie_kamp',         $ditjaar_part_notificatie_kamp);
    wachthond($extdebug,3, 'ditjaar_part_notificatie_staf',         $ditjaar_part_notificatie_staf);
    wachthond($extdebug,3, 'ditjaar_part_notificatie_priv',         $ditjaar_part_notificatie_priv);

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 1.0 VERWIJDER CURRENT NOTIF EMAILS",               $displayname);
    wachthond($extdebug,2, "########################################################################");

    ##########################################################################################
    // M61: EERST VERWIJDEREN BECAUSE DOUBLES WERE CREATED: THIS SHOULD NOT HAPPEN > TODO)
    ##########################################################################################  
    
    // 16 notif_deel
    // 17 notif_leid
    // 18 notif_kamp
    // 19 notif_staf

    $result_email_notif_delete = civicrm_api4('Email', 'delete', [
        'where' => [
          ['location_type_id', 'IN', [16, 17, 18, 19]],
          ['contact_id', '=', $contact_id],
        ],
    ]);
    wachthond($extdebug,3, "EXISTING notif_* emails", "VERWIJDERD");

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 1.1 GET CURRENT EMAILADRESSS",                     $displayname);
    wachthond($extdebug,2, "########################################################################");

    $params_email_get = [
        'checkPermissions' => FALSE,
        'debug' => $apidebug,
        'select' => [
            'row_count',
            'id', 
            'email',
            'location_type_id',
            'is_primary',
            'on_hold',
            'is_active',
        ],
        'where' => [
            ['contact_id', '=', $contact_id],
        ],
        'orderBy' => [
            'location_type_id' => 'ASC',
        ],
    ];
    wachthond($extdebug,3, 'params_email_get',      $params_email_get);
    $result_email_get = civicrm_api4('Email','get', $params_email_get);
    wachthond($extdebug,9, 'result_email_get',      $result_email_get);

    // Maak een array, ook als de resultset leeg is
    $result_email_get_array = $result_email_get->getArrayCopy() ?? [];
    wachthond($extdebug,9, 'result_array', $result_email_get_array);

    $key_home         = array_search(1,  array_column($result_email_get_array, 'location_type_id'));
    $key_othr         = array_search(4,  array_column($result_email_get_array, 'location_type_id'));
    $key_kamp         = array_search(24, array_column($result_email_get_array, 'location_type_id'));
    $key_oud1         = array_search(11, array_column($result_email_get_array, 'location_type_id'));
    $key_oud2         = array_search(12, array_column($result_email_get_array, 'location_type_id'));
    $key_deel         = array_search(10, array_column($result_email_get_array, 'location_type_id'));
    $key_gave         = array_search(26, array_column($result_email_get_array, 'location_type_id'));
    $key_work         = array_search(2,  array_column($result_email_get_array, 'location_type_id'));
    $key_priv         = array_search(20, array_column($result_email_get_array, 'location_type_id'));

    $key_notif_deel   = array_search(16, array_column($result_email_get_array, 'location_type_id'));
    $key_notif_leid   = array_search(17, array_column($result_email_get_array, 'location_type_id'));
    $key_notif_kamp   = array_search(18, array_column($result_email_get_array, 'location_type_id'));
    $key_notif_staf   = array_search(19, array_column($result_email_get_array, 'location_type_id'));

    $email_home_email     = NULL;
    $email_othr_email     = NULL;
    $email_kamp_email     = NULL;
    $email_oud1_email     = NULL;
    $email_oud2_email     = NULL;
    $email_deel_email     = NULL;
    $email_leid_email     = NULL;
    $email_gave_email     = NULL;
    $email_work_email     = NULL;
    $email_priv_email     = NULL;

    $email_plac_email     = NULL;
    $email_onvr_email     = NULL;

    $notif_deel_email     = NULL;
    $notif_leid_email     = NULL;
    $notif_kamp_email     = NULL;
    $notif_staf_email     = NULL;

    $new_email_home_email = NULL;
    $new_email_othr_email = NULL;
    $new_email_home_email = NULL;
    $new_email_pric_email = NULL;

    if (is_numeric($key_home))       { $email_home_email = $result_email_get_array[$key_home]['email']      ?? NULL;}
    if (is_numeric($key_othr))       { $email_othr_email = $result_email_get_array[$key_othr]['email']      ?? NULL;}
    if (is_numeric($key_kamp))       { $email_kamp_email = $result_email_get_array[$key_kamp]['email']      ?? NULL;}
    if (is_numeric($key_oud1))       { $email_oud1_email = $result_email_get_array[$key_oud1]['email']      ?? NULL;}
    if (is_numeric($key_oud2))       { $email_oud2_email = $result_email_get_array[$key_oud2]['email']      ?? NULL;}           
    if (is_numeric($key_deel))       { $email_deel_email = $result_email_get_array[$key_deel]['email']      ?? NULL;}
    if (is_numeric($key_gave))       { $email_gave_email = $result_email_get_array[$key_gave]['email']      ?? NULL;}
    if (is_numeric($key_work))       { $email_work_email = $result_email_get_array[$key_work]['email']      ?? NULL;}
    if (is_numeric($key_priv))       { $email_priv_email = $result_email_get_array[$key_priv]['email']      ?? NULL;}

    if (is_numeric($key_notif_deel)) {$notif_deel_email = $result_email_get_array[$key_notif_deel]['email'] ?? NULL;}
    if (is_numeric($key_notif_leid)) {$notif_leid_email = $result_email_get_array[$key_notif_leid]['email'] ?? NULL;}
    if (is_numeric($key_notif_kamp)) {$notif_kamp_email = $result_email_get_array[$key_notif_kamp]['email'] ?? NULL;}
    if (is_numeric($key_notif_staf)) {$notif_staf_email = $result_email_get_array[$key_notif_staf]['email'] ?? NULL;}

    if (is_numeric($key_home))       { $email_home_id   = $result_email_get_array[$key_home]['id']          ?? NULL;}
    if (is_numeric($key_othr))       { $email_othr_id   = $result_email_get_array[$key_othr]['id']          ?? NULL;}
    if (is_numeric($key_kamp))       { $email_kamp_id   = $result_email_get_array[$key_kamp]['id']          ?? NULL;}
    if (is_numeric($key_oud1))       { $email_oud1_id   = $result_email_get_array[$key_oud1]['id']          ?? NULL;}
    if (is_numeric($key_oud2))       { $email_oud2_id   = $result_email_get_array[$key_oud2]['id']          ?? NULL;}
    if (is_numeric($key_deel))       { $email_deel_id   = $result_email_get_array[$key_deel]['id']          ?? NULL;}
    if (is_numeric($key_gave))       { $email_gave_id   = $result_email_get_array[$key_gave]['id']          ?? NULL;}
    if (is_numeric($key_work))       { $email_work_id   = $result_email_get_array[$key_work]['id']          ?? NULL;}
    if (is_numeric($key_priv))       { $email_priv_id   = $result_email_get_array[$key_priv]['id']          ?? NULL;}

    if (is_numeric($key_notif_deel)) { $notif_deel_id   = $result_email_get_array[$key_notif_deel]['id']    ?? NULL;}
    if (is_numeric($key_notif_leid)) { $notif_leid_id   = $result_email_get_array[$key_notif_leid]['id']    ?? NULL;}
    if (is_numeric($key_notif_kamp)) { $notif_kamp_id   = $result_email_get_array[$key_notif_kamp]['id']    ?? NULL;}
    if (is_numeric($key_notif_staf)) { $notif_staf_id   = $result_email_get_array[$key_notif_staf]['id']    ?? NULL;}

    if (in_array($ditjaar_part_functie, array('hoofdleiding', 'kernteamlid', 'bestuurslid')) OR $hoofdleiding_mailboxaccount == 1) {

        wachthond($extdebug,3, 'key_home',          $key_home);
        wachthond($extdebug,3, 'key_othr',          $key_othr);
        wachthond($extdebug,3, 'key_kamp',          $key_kamp);
        wachthond($extdebug,3, 'key_gave',          $key_gave);
        wachthond($extdebug,3, 'key_work',          $key_work);
        wachthond($extdebug,3, 'key_priv',          $key_priv);

        wachthond($extdebug,3, 'key_notif_deel',    $key_notif_deel);
        wachthond($extdebug,3, 'key_notif_leid',    $key_notif_leid);
        wachthond($extdebug,3, 'key_notif_kamp',    $key_notif_kamp);
        wachthond($extdebug,3, 'key_notif_staf',    $key_notif_staf);
    }

    wachthond($extdebug,3, 'email_home_email',      $email_home_email);
    wachthond($extdebug,3, 'email_othr_email',      $email_othr_email);
    wachthond($extdebug,3, 'email_kamp_email',      $email_kamp_email);
    wachthond($extdebug,3, 'email_oud1_email',      $email_oud1_email);
    wachthond($extdebug,3, 'email_oud2_email',      $email_oud2_email);
    wachthond($extdebug,3, 'email_deel_email',      $email_deel_email);
    wachthond($extdebug,3, 'email_work_email',      $email_work_email);
    wachthond($extdebug,3, 'email_priv_email',      $email_priv_email);

    if (in_array($ditjaar_part_functie, array('hoofdleiding', 'kernteamlid', 'bestuurslid')) OR $hoofdleiding_mailboxaccount == 1) {

        wachthond($extdebug,3, 'notif_deel_email',  $notif_deel_email);
        wachthond($extdebug,3, 'notif_leid_email',  $notif_leid_email);
        wachthond($extdebug,3, 'notif_kamp_email',  $notif_kamp_email);
        wachthond($extdebug,3, 'notif_staf_email',  $notif_staf_email);
    }

    wachthond($extdebug,3, 'email_home_id',         $email_home_id);
    wachthond($extdebug,3, 'email_othr_id',         $email_othr_id);
    wachthond($extdebug,3, 'email_kamp_id',         $email_kamp_id);
    wachthond($extdebug,3, 'email_oud1_id',         $email_oud1_id);
    wachthond($extdebug,3, 'email_oud2_id',         $email_oud2_id);
    wachthond($extdebug,3, 'email_deel_id',         $email_deel_id);
    wachthond($extdebug,3, 'email_gave_id',         $email_gave_id);
    wachthond($extdebug,3, 'email_work_id',         $email_work_id);
    wachthond($extdebug,3, 'email_priv_id',         $email_priv_id);  

    if (in_array($ditjaar_part_functie, array('hoofdleiding', 'kernteamlid', 'bestuurslid')) OR $hoofdleiding_mailboxaccount == 1) {

        wachthond($extdebug,3, 'notif_deel_id',     $notif_deel_id);
        wachthond($extdebug,3, 'notif_leid_id',     $notif_leid_id);
        wachthond($extdebug,3, 'notif_kamp_id',     $notif_kamp_id);
        wachthond($extdebug,3, 'notif_staf_id',     $notif_staf_id);
    }

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 1.2 GET CURRENT PRIMARY EMAILADRESS",              $displayname);
    wachthond($extdebug,2, "########################################################################");

    $key_prim = array_search(1,  array_column($result_email_get_array, 'is_primary'));
    if (is_numeric($key_prim))  { $email_prim_email = $result_email_get_array[$key_prim]['email'] ?? NULL;}

    wachthond($extdebug,1, 'email_prim_email',                                      $email_prim_email);

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 1.3 CONSTRUCT THE PROPER EMAIL_OTHR",              $displayname);
    wachthond($extdebug,2, "########################################################################");

    // --- STAP 1: Basis variabelen klaarzetten ---
    $email_plac_email = (!empty($user_name)) ? $user_name . "@placeholder.nl" : null;
    $email_onvr_email = (!empty($user_name)) ? $user_name . "@onvergetelijk.nl" : null;

    // Detecteer of de primaire email defect is
    $primary_is_broken  = false;
    $primary_found      = false;

    foreach ($result_email_get_array as $em) {
        // Debug: Toon elk record dat de loop controleert
        wachthond($extdebug, 3, "CHECK EMAIL record voor $displayname", 
            "ID: {$em['id']} | Mail: {$em['email']} | Prim: {$em['is_primary']} | Hold: {$em['on_hold']}");

        if ((int)$em['is_primary'] === 1) {
            $primary_found = true;
            
            // Check alleen op on_hold (omdat is_active niet bestaat in de API resultset)
            $hold_status = (int)$em['on_hold'];

            if ($hold_status === 1) {
                $primary_is_broken = true;
                wachthond($extdebug, 1, "!!! DETECTIE BROKEN (ON-HOLD) !!!", 
                    "Contact: $displayname | Mail: {$em['email']} | Hold status: $hold_status");
            } else {
                wachthond($extdebug, 2, "PRIMARY EMAIL GEZOND", 
                    "Mail: {$em['email']} is niet on-hold.");
            }
            break; 
        }
    }

    if (!$primary_found) {
        // Als er geen primaire mail is, kunnen we niks sturen, dus fallback naar placeholder
        $primary_is_broken = true; 
        wachthond($extdebug, 1, "SYNC WARNING", "Geen record met is_primary=1 gevonden voor $displayname. Forceer placeholder.");
    }

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 1.5 BEPAAL WAARDE USER_MAIL",                      $displayname);
    wachthond($extdebug,2, "########################################################################");

    // PRIORITEIT 1: Is de mail defect? ALTIJD placeholder (geen uitzondering voor leiding)
    if ($primary_is_broken) {
        $user_mail              = $email_plac_email;
        $new_email_othr_email   = $email_plac_email;
        wachthond($extdebug, 1, "BESLUIT: Placeholder wegens defecte mail", $user_mail);
    }
    // PRIORITEIT 2: Staf / Hoofdleiding
    elseif (in_array($ditjaar_part_functie, ['hoofdleiding', 'kernteamlid', 'kampstaf', 'bestuurslid'])) {
        $user_mail              = $email_onvr_email;
        $new_email_othr_email   = $email_onvr_email;
        wachthond($extdebug, 1, "BESLUIT: Staf-email", $user_mail);
    }
    // PRIORITEIT 3: Leiding (Alleen als mail NIET broken is!)
    elseif ($email_home_email && ($ditjaarleidyes == 1 || $ditjaarleidmss == 1)) {
        $user_mail              = $email_home_email;
        $new_email_othr_email   = $email_home_email;
        wachthond($extdebug, 1, "BESLUIT: Leiding-email (Home)", $user_mail);
    } else {
        if ($leeftijd_vantoday_decimalen < 18) {
            $user_mail              = $email_plac_email;
            $new_email_othr_email   = $email_plac_email;
            wachthond($extdebug, 2, "BESLUIT: Deelnemer < 18 (Placeholder)");
        } elseif ($leeftijd_vantoday_decimalen >= 18 && $email_deel_email) {
            $user_mail              = $email_deel_email;
            $new_email_othr_email   = $email_deel_email;
            wachthond($extdebug, 2, "BESLUIT: Deelnemer >= 18 (Eigen email_deel)");
        } elseif ($email_home_email) {
            $user_mail              = $email_home_email;
            $new_email_othr_email   = $email_home_email;
            wachthond($extdebug, 2, "BESLUIT: (Email_home)");
        } else {
            $user_mail              = $email_prim_email;
            $new_email_othr_email   = $email_prim_email;
            wachthond($extdebug, 2, "BESLUIT: (Email_prim)");
        }
    }

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 1.6 UPDATE EMAIL_PRIV MET WAARDE DIT EVENT",       $displayname);
    wachthond($extdebug,2, "########################################################################");

    if (in_array($ditjaar_part_functie, array('hoofdleiding', 'kernteamlid', 'kampstaf', 'bestuurslid')) OR $hoofdleiding_mailboxaccount == 1) {

        // UPDATE EMAIL_PRIV MET WAARDE DIT EVENT, HOUD ANDERS HETZELFDE (M61: TODO alleen in huidig kalenderjaar)

        wachthond($extdebug,3, 'ditjaar_part_notificatie_priv', $ditjaar_part_notificatie_priv);
        wachthond($extdebug,3, 'email_priv_email',              $email_priv_email);

        if ($ditjaar_part_notificatie_priv) {
            $new_email_priv_email = $ditjaar_part_notificatie_priv;
        } elseif (!empty($email_priv_email)) {
            $new_email_priv_email = $email_priv_email;
        } else {
            $new_email_priv_email = NULL;
        }

        wachthond($extdebug,3, 'new_email_priv_email',      $new_email_priv_email);
    }

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 1.7 BEPAAL WAARDE HOME_EMAIL (>18)",               $displayname);
    wachthond($extdebug,2, "########################################################################");

    // ALS ER EEN EMAIL_PRIV IS DOORGEGEVEN (DOOR HOOFDLEIDING) GEBRUIK DEZE DAN ALS EMAIL_HOME

    if ($new_email_priv_email AND $ditjaarleidyes == 1) {
        $new_email_home_email = $new_email_priv_email;
    } else {
        $new_email_home_email = $email_home_email;
    }

    wachthond($extdebug,3, 'new_email_home_email',      $new_email_home_email);

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 1.8 UPDATE EMAIL_HOME IGV EEN NIEUWE USERNAME",    $displayname);
    wachthond($extdebug,2, "########################################################################");

    // UPDATE EMAIL_HOME INDIEN DIE @ONVERGETELIJK.NL WAS EN ER IS EEN NIEUWE USERNAME (ACHTERNAAM/GETROUWD)

    if ($email_home_email) {

        $emaildomain = array_pop(explode('@', $email_home_email));
        wachthond($extdebug,3, 'emaildomain', $emaildomain);

        if ($emaildomain == 'onvergetelijk.nl') {
            $new_email_home_email = $email_onvr_email;
            wachthond($extdebug,2, "email_home_email ($email_home_email) GEUPDATED NAAR", "$email_onvr_email");
        } else {
            $new_email_home_email = $new_email_home_email;
            wachthond($extdebug,2, "email_home_email BEHOUDEN", "$new_email_home_email");
        }
        // M61: TODO: mss niet new_email omdat iemand dit jaar evt geen staf of HL is
    }

    if ($ditjaar_part_kamptype_naam AND $ditjaar_part_kampweek_nr) {
        $new_email_kamp_email = $ditjaar_part_kamptype_naam.$ditjaar_part_kampweek_nr."@onvergetelijk.nl";
    } else {
        $new_email_kamp_email = NULL;
    }

    wachthond($extdebug,3, 'ditjaar_functie',                       $ditjaar_part_functie);
    wachthond($extdebug,3, 'ditjaar_event_weeknr',                  $ditjaar_event_weeknr);
    wachthond($extdebug,3, 'ditjaar_part_kamptype_naam',            $ditjaar_part_kamptype_naam);

    if (in_array($ditjaar_part_functie, array('hoofdleiding', 'kernteamlid', 'kampstaf', 'bestuurslid')) OR $hoofdleiding_mailboxaccount == 1) {

        wachthond($extdebug,2, "########################################################################");
        wachthond($extdebug,2, "### EMAIL 2.0 BEPAAL WAARDEN NOTIFICATIE EMAILS",            $displayname);
        wachthond($extdebug,2, "########################################################################");

        if ($ditjaar_part_notificatie_deel) { $cont_notificatie_deel = $ditjaar_part_notificatie_deel;  }
        if ($ditjaar_part_notificatie_leid) { $cont_notificatie_leid = $ditjaar_part_notificatie_leid;  }
        if ($ditjaar_part_notificatie_kamp) { $cont_notificatie_kamp = $ditjaar_part_notificatie_kamp;  }
        if ($ditjaar_part_notificatie_staf) { $cont_notificatie_staf = $ditjaar_part_notificatie_staf;  }

        wachthond($extdebug,3, 'cont_notificatie_deel', $cont_notificatie_deel);
        wachthond($extdebug,3, 'cont_notificatie_leid', $cont_notificatie_leid);
        wachthond($extdebug,3, 'cont_notificatie_kamp', $cont_notificatie_kamp);
        wachthond($extdebug,3, 'cont_notificatie_staf', $cont_notificatie_staf);

        wachthond($extdebug,3, 'new_email_priv_email',  $new_email_priv_email);
        wachthond($extdebug,3, 'new_email_work_email',  $new_email_work_email);
        wachthond($extdebug,3, 'new_email_kamp_email',  $new_email_kamp_email);

        // ALLEEN VOOR HOOFDLEIDING: maak notif emails aan ook indien er nog geen voorkeur is doorgegeven

        if (in_array($ditjaar_part_functie, array('hoofdleiding'))) {

            // DEFAULT 1 INDIEN NOG GEEN VOORKEUR DAN PERSOONLIJKE ONVERGETELIJK MAIL
            if (empty($cont_notificatie_deel))          {   $new_notif_deel_email = $new_email_work_email;  }
            if (empty($cont_notificatie_leid))          {   $new_notif_leid_email = $new_email_work_email;  }
            if (empty($cont_notificatie_kamp))          {   $new_notif_kamp_email = $new_email_work_email;  }
            if (empty($cont_notificatie_staf))          {   $new_notif_staf_email = $new_email_work_email;  }

            // DEFAULT 2 OVERRULE MET PRIVE
            if (!empty($new_email_priv_email))          {   $new_notif_deel_email = $new_email_priv_email;  }
            if (!empty($new_email_priv_email))          {   $new_notif_leid_email = $new_email_priv_email;  }
            if (!empty($new_email_priv_email))          {   $new_notif_kamp_email = $new_email_priv_email;  }
            if (!empty($new_email_priv_email))          {   $new_notif_staf_email = $new_email_priv_email;  }
        }

        // INDIEN VOORKEUR VOOR ALGEMENE EMAILBOX MAAK DAN GEEN NOTIF EMAIL AAN
        if ($cont_notificatie_deel == 'kampmail')   {   $new_notif_deel_email = NULL;  }
        if ($cont_notificatie_leid == 'kampmail')   {   $new_notif_leid_email = NULL;  }
        if ($cont_notificatie_kamp == 'kampmail')   {   $new_notif_kamp_email = NULL;  }
        if ($cont_notificatie_staf == 'kampmail')   {   $new_notif_staf_email = NULL;  }

        if ($cont_notificatie_deel == 'kamppers')   {   $new_notif_deel_email = $new_email_work_email;  }
        if ($cont_notificatie_leid == 'kamppers')   {   $new_notif_leid_email = $new_email_work_email;  }
        if ($cont_notificatie_kamp == 'kamppers')   {   $new_notif_kamp_email = $new_email_work_email;  }
        if ($cont_notificatie_staf == 'kamppers')   {   $new_notif_staf_email = $new_email_work_email;  }

        if ($cont_notificatie_deel == 'privemail')  {   $new_notif_deel_email = $new_email_priv_email;  }
        if ($cont_notificatie_leid == 'privemail')  {   $new_notif_leid_email = $new_email_priv_email;  }
        if ($cont_notificatie_kamp == 'privemail')  {   $new_notif_kamp_email = $new_email_priv_email;  }
        if ($cont_notificatie_staf == 'privemail')  {   $new_notif_staf_email = $new_email_priv_email;  }

        if (in_array($lastname, array("kinderkamp1","kinderkamp2","brugkamp1","brugkamp2","tienerkamp1","tienerkamp2","jeugdkamp1","jeugdkamp2","topkamp"))) {
            $new_notif_deel_email = $new_email_work_email;
            $new_notif_leid_email = $new_email_work_email;
            $new_notif_kamp_email = $new_email_work_email;
            $new_notif_staf_email = $new_email_work_email;
        }

        wachthond($extdebug,3, 'new_notif_deel_email',  $new_notif_deel_email);
        wachthond($extdebug,3, 'new_notif_leid_email',  $new_notif_leid_email);
        wachthond($extdebug,3, 'new_notif_kamp_email',  $new_notif_kamp_email);
        wachthond($extdebug,3, 'new_notif_staf_email',  $new_notif_staf_email);

        email_civicrm_update($contact_id, 'notif_deel', $notif_deel_id, $notif_deel_email, $new_notif_deel_email);
        email_civicrm_update($contact_id, 'notif_leid', $notif_leid_id, $notif_leid_email, $new_notif_leid_email);
        email_civicrm_update($contact_id, 'notif_kamp', $notif_kamp_id, $notif_kamp_email, $new_notif_kamp_email);
        email_civicrm_update($contact_id, 'notif_staf', $notif_staf_id, $notif_staf_email, $new_notif_staf_email);

        if ($new_notif_staf_email) {
            $new_email_home_email = $new_notif_staf_email;
            wachthond($extdebug,2, "GEBRUIK VOOR email_home_email EMAIL VOORKEUR STAF", "$new_notif_staf_email");        
        }
    }

    if (in_array($ditjaar_part_functie, array('hoofdleiding', 'kernteamlid', 'kampstaf', 'bestuurslid')) OR $hoofdleiding_mailboxaccount == 1) {

        wachthond($extdebug, 2, "########################################################################");
        wachthond($extdebug, 1, "### EMAIL 2.1 SYNC EMAILS NOTIFICATIE DEEL > GOOGLE GROUPS", $displayname);
        wachthond($extdebug, 2, "########################################################################");

        // 1. Verzamel alle mogelijke oude adressen en filter NULL waarden
        $raw_unsubscribe_list = [
            $email_priv_email       ?? NULL,
            $email_work_email       ?? NULL,
            $new_email_priv_email   ?? NULL,
            $new_email_work_email   ?? NULL,
        ];

        // 2. Maak de lijst UNIEK en verwijder lege waarden (voorkomt dubbele delete requests)
        $clean_unsubscribe_list = array_values(array_unique(array_filter($raw_unsubscribe_list)));

        // 3. Bepaal de Google Group ID
        $googlegroup_notifdeel = match($ditjaar_part_kampkort) {
            'kk1'   => '01baon6m3wo0451',
            'kk2'   => '00vx12273fgfnd5',
            'bk1'   => '00lnxbz9161bbzw',
            'bk2'   => '0147n2zr2s87rx7',
            'tk1'   => '02xcytpi1fs7xwo',
            'tk2'   => '01opuj5n2028q4s',
            'jk1'   => '02bn6wsx3827ior',
            'jk2'   => '030j0zll0m5pg5h',
            'top'   => '00haapch3zvbjru',
            default => NULL,
        };

        if ($googlegroup_notifdeel) {
            
            // --- A. VERWIJDEREN OUDE ADRESSEN ---
            // De functie googlegroup_deletemember handelt nu de API call en foutmeldingen af
            if (!empty($clean_unsubscribe_list)) {        
                googlegroup_deletemember($googlegroup_notifdeel, $clean_unsubscribe_list);
                wachthond($extdebug, 2, 'Aanroep googlegroup_deletemember gedaan voor groep', $googlegroup_notifdeel);
            }

            // --- B. TOEVOEGEN NIEUW ADRES ---
            // De functie googlegroup_subscribe handelt nu de API call en foutmeldingen af
            if (!empty($new_notif_deel_email)) {
                
                // We maken de batch array aan: [ 'Weergavenaam' => 'emailadres' ]
                // Let op: in je originele code stond "$displayname 0", ik heb hier de schone $displayname gebruikt.
                $subscribe_batch = [
                    $displayname => $new_notif_deel_email
                ];

                googlegroup_subscribe($googlegroup_notifdeel, $subscribe_batch);
                
                wachthond($extdebug, 2, 'Aanroep googlegroup_subscribe gedaan voor', "$displayname -> $new_notif_deel_email");
            }

        } else {
            wachthond($extdebug, 1, "SKIP SYNC: Geen Google Group ID gevonden voor kamp", $ditjaar_part_kampkort);
        }
    }

    if (in_array($ditjaar_part_functie, array('hoofdleiding', 'kernteamlid', 'kampstaf', 'bestuurslid')) OR $hoofdleiding_mailboxaccount == 1) {

        wachthond($extdebug, 2, "########################################################################");
        wachthond($extdebug, 1, "### EMAIL 2.2 SYNC EMAILS NOTIFICATIE LEID > GOOGLE GROUPS", $displayname);
        wachthond($extdebug, 2, "########################################################################");

        // 1. Verzamel en filter adressen (Uniek maken voorkomt API-blokkades)
        $raw_unsubscribe_list = [
            $email_priv_email     ?? NULL,
            $email_work_email     ?? NULL,
            $new_email_priv_email ?? NULL,
            $new_email_work_email ?? NULL,
        ];
        $clean_unsubscribe_list = array_values(array_unique(array_filter($raw_unsubscribe_list)));

        // 2. Bepaal de Google Group ID voor Leiding Notificaties
        $googlegroup_notifleid = match($ditjaar_part_kampkort) {
            'kk1'   => '00gjdgxs35zo5jv',
            'kk2'   => '01302m921zcwabc',
            'bk1'   => '0319y80a3e30qbg',
            'bk2'   => '00kgcv8k0qhgq6a',
            'tk1'   => '02s8eyo137koo5d',
            'tk2'   => '00kgcv8k2o7zuqf',
            'jk1'   => '03oy7u294kt7vtn',
            'jk2'   => '04i7ojhp2hwyjk2',
            'top'   => '017dp8vu3t3rwb4',
            default => NULL,
        };

        if ($googlegroup_notifleid) {

            // --- A. VERWIJDEREN OUDE ADRESSEN ---
            if (!empty($clean_unsubscribe_list)) {
                googlegroup_deletemember($googlegroup_notifleid, $clean_unsubscribe_list);            
                wachthond($extdebug, 2, 'Aanroep googlegroup_deletemember (Leid) gedaan voor groep', $googlegroup_notifleid);
            }

            // --- B. TOEVOEGEN NIEUW ADRES ---
            if (!empty($new_notif_leid_email)) {
                
                // Batch array samenstellen: [ Naam => Email ]
                $subscribe_batch = [
                    $displayname => $new_notif_leid_email
                ];

                // De helper-functie regelt nu de API-call en foutafhandeling
                googlegroup_subscribe($googlegroup_notifleid, $subscribe_batch);
                wachthond($extdebug, 2, 'Aanroep googlegroup_subscribe (Leid) gedaan voor', "$displayname -> $new_notif_leid_email");
            }

        }
    }

if (in_array($ditjaar_part_functie, array('hoofdleiding', 'kernteamlid', 'kampstaf', 'bestuurslid')) OR $hoofdleiding_mailboxaccount == 1) {

        wachthond($extdebug, 2, "########################################################################");
        wachthond($extdebug, 1, "### EMAIL 2.3 SYNC EMAILS NOTIFICATIE KAMP > GOOGLE GROUPS", $displayname);
        wachthond($extdebug, 2, "########################################################################");

        // 1. Verzamel en filter adressen
        $raw_unsubscribe_list = [
            $email_priv_email     ?? NULL,
            $email_work_email     ?? NULL,
            $new_email_priv_email ?? NULL,
            $new_email_work_email ?? NULL,
        ];
        $clean_unsubscribe_list = array_values(array_unique(array_filter($raw_unsubscribe_list)));

        // 2. Bepaal de Google Group ID voor Kamp Notificaties
        $googlegroup_notifkamp = match($ditjaar_part_kampkort) {
            'kk1'   => '03whwml44cp9k45',
            'kk2'   => '02fk6b3p0ikk00x',
            'bk1'   => '049x2ik50nuxsf9',
            'bk2'   => '03rdcrjn4882pxp',
            'tk1'   => '01ksv4uv0jwe4ss',
            'tk2'   => '03l18frh2n6xt74',
            'jk1'   => '01baon6m39ciju5',
            'jk2'   => '0279ka6533bbb65',
            'top'   => '00kgcv8k1t3von2',
            default => NULL,
        };

        if ($googlegroup_notifkamp) {

            // --- A. VERWIJDEREN OUDE ADRESSEN ---
            if (!empty($clean_unsubscribe_list)) {
                
                // De helper-functie regelt nu de API-call en foutafhandeling
                googlegroup_deletemember($googlegroup_notifkamp, $clean_unsubscribe_list);
                
                wachthond($extdebug, 2, 'Aanroep googlegroup_deletemember (Kamp) gedaan voor groep', $googlegroup_notifkamp);
            }

            // --- B. TOEVOEGEN NIEUW ADRES ---
            if (!empty($new_notif_kamp_email)) {
                
                // Batch array samenstellen: [ Naam => Email ]
                $subscribe_batch = [
                    $displayname => $new_notif_kamp_email
                ];

                // De helper-functie regelt nu de API-call en foutafhandeling
                googlegroup_subscribe($googlegroup_notifkamp, $subscribe_batch);
                
                wachthond($extdebug, 2, 'Aanroep googlegroup_subscribe (Kamp) gedaan voor', "$displayname -> $new_notif_kamp_email");
            }
        }
    }

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 3.1 CREATE OR UPDATE NOTIFICATION EMAIL_OTHR",     $displayname);
    wachthond($extdebug,2, "########################################################################");

    email_civicrm_update($contact_id, 'Other', $email_othr_id, $email_othr_email, $new_email_othr_email);

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 3.2 CREATE OR UPDATE NOTIFICATION EMAIL_HOME",     $displayname);
    wachthond($extdebug,2, "########################################################################");

    email_civicrm_update($contact_id, 'Home', $email_home_id, $email_home_email, $new_email_home_email);

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 3.3 CREATE OR UPDATE NOTIFICATION EMAIL_WORK",     $displayname);
    wachthond($extdebug,2, "########################################################################");

    email_civicrm_update($contact_id, 'Work', $email_work_id, $email_work_email, $new_email_work_email);

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 3.4 CREATE OR UPDATE NOTIFICATION EMAIL_KAMP",     $displayname);
    wachthond($extdebug,2, "########################################################################");

    email_civicrm_update($contact_id, 'Kamp', $email_kamp_id, $email_kamp_email, $new_email_kamp_email);

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 3.5 CREATE OR UPDATE NOTIFICATION EMAIL_PRIV",     $displayname);
    wachthond($extdebug,2, "########################################################################");

    email_civicrm_update($contact_id, 'Prive', $email_priv_id, $email_priv_email, $new_email_priv_email);

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 3.6 CREATE OR UPDATE NOTIFICATION EMAIL_GAVE",     $displayname);
    wachthond($extdebug,2, "########################################################################");

    email_civicrm_update($contact_id, 'Gave', $email_gave_id, $email_gave_email, $new_email_gave_email);    

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 4.1 CREATE EMAIL_HOME FROM DEEL IF >=21", $leeftijd_vantoday_decimalen);
    wachthond($extdebug,2, "########################################################################");

    if (empty($email_home_id) AND $email_deel_id > 0 AND $leeftijd_vantoday_rondjaren >= 21) {

        if (!empty($email_deel_email)) {
            $new_email_home_email = $email_deel_email;
        }
        $params_email_home_create = [
            'checkPermissions' => FALSE,
            'debug' => $apidebug,
            'values' => [
                'contact_id'            => $contact_id,
                'email'                 => $new_email_home_email,
                'location_type_id:name' => "Home",
                'is_primary'            =>  TRUE,
            ],
        ];
        wachthond($extdebug,7, 'params_email_home_create',              $params_email_home_create);
        if ($extwrite == 1 AND !in_array($privacy_voorkeuren, array("33","44"))) {
            $result_email_home_create = civicrm_api4('Email', 'create', $params_email_home_create);
        }
        wachthond($extdebug,9, 'result_email_create',                   $result_email_home_create);
        wachthond($extdebug,1, 'email_home aangemaakt voor oud-deelnemer',  $new_email_home_email);
    }

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 4.2 DELETE EMAIL_DEEL FROM CONTACT IF >=21", $leeftijd_vantoday_decimalen);
    wachthond($extdebug,2, "########################################################################");

    if ($email_home_id > 0 AND $email_deel_id > 0 AND $leeftijd_vantoday_rondjaren >= 21) {

        if ($email_home_email == $email_deel_email) {

            // M61: DELETE EMAIL_DEEL IF SAME AS EMAIL_HOME
            $result_email_deel_delete = civicrm_api4('Email', 'delete', [
                'where' => [
                    ['id',            '=', $email_deel_id]
                ],
                'checkPermissions' => FALSE,
            ]);
            $email_deel_removed = 1;
            wachthond($extdebug,2, 'EMAIL_DEEL VERWIJDERD WANT == EMAIL_HOME', "$email_deel_email ($email_deel_id)");
        }
    }

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 4.3 DELETE EMAIL_HOME FROM CONTACT IF <18", $leeftijd_vantoday_decimalen);
    wachthond($extdebug,2, "########################################################################");

    if ($email_home_id > 0 AND $email_oud1_id > 0 AND $leeftijd_vantoday_rondjaren >= 6 AND $leeftijd_vantoday_rondjaren < 18) {

        if (empty($datum_belangstelling)) {

            // M61: TODO TIJDELIJK ALS CLEANUP VOOR MULTIPLE PRIVE EMAILS
            $result_email_home_delete = civicrm_api4('Email', 'delete', [
                'where' => [
                    ['contact_id',              '=', $contact_id],
                    ['location_type_id:name',   '=', 'Home'],
                ],
                'checkPermissions' => FALSE,
            ]);
            $email_home_removed = 1;
            wachthond($extdebug,2, 'email_home verwijderd (alle van dit contact)', "$email_home_email ($email_home_id)");

        }
    }

    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 2, "### EMAIL 5.1 SET PROPER EMAIL PRIMARY",                     $displayname);
    wachthond($extdebug, 2, "########################################################################");

    $target_primary_id = NULL;
    $reason = "Geen match gevonden";

    // 1. Logica voor bepalen welke ID de primary moet worden
    wachthond($extdebug, 3, "DEBUG ID CHECK", "OUD1: $email_oud1_id | GAVE: $email_gave_id | HOME: $email_home_id");

    if ($leeftijd_vantoday_rondjaren > 0 && $leeftijd_vantoday_rondjaren < 18) {
        if ($email_gave_id > 0) {
            $target_primary_id = $email_gave_id;
            $reason = "Kind < 18 met GAVE record (Gave krijgt voorrang)";
        } elseif ($email_oud1_id > 0) {
            $target_primary_id = $email_oud1_id;
            $reason = "Kind < 18, OUD1 is de juiste primary";
        } else {
            $reason = "Kind < 18, maar GEEN OUD1 of GAVE gevonden om primary te maken!";
        }
    } elseif ($leeftijd_vantoday_rondjaren >= 18) {
        if ($email_home_id > 0) {
            $target_primary_id = $email_home_id;
            $reason = "Volwassene >= 18, HOME is de juiste primary";
        } else {
            $reason = "Volwassene >= 18, maar GEEN HOME gevonden";
        }
    }

    // Log het besluit
    wachthond($extdebug, 1, "PRIMARY BESLUIT", "Target ID: " . ($target_primary_id ?? 'NULL') . " | Reden: $reason");

    // 2. Uitvoeren van de finale update volgens gevraagde syntax
    if ($target_primary_id && $extwrite == 1) {
        
        if (in_array($privacy_voorkeuren, ["33", "44"])) {
            wachthond($extdebug, 1, "SKIP UPDATE", "Primary niet gezet wegens privacy voorkeuren (33/44)");
        } else {
            // Defineer parameters
            $params_email_primary_update = [
                'checkPermissions' => FALSE,
                'debug' => $apidebug,
                'where' => [
                    ['id',          '=', $target_primary_id],
                    ['contact_id',  '=', $contact_id],
                ],
                'values' => [
                    'is_primary' => TRUE,
                ],
            ];

            wachthond($extdebug, 7, 'params_email_primary_update', $params_email_primary_update);

            try {
                // Voer update uit
                $result_email_primary_update = civicrm_api4('Email', 'update', $params_email_primary_update);
                
                wachthond($extdebug, 9, 'result_email_primary_update', $result_email_primary_update);
                wachthond($extdebug, 2, "PRIMARY SUCCES", "ID $target_primary_id is nu primary voor $displayname");
            } catch (\Exception $e) {
                wachthond($extdebug, 1, "API ERROR", "Fout bij zetten primary: " . $e->getMessage());
            }
        }
    }

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### EMAIL 6.0 PREPARE ARRAY EMAILADRESSS",                   $displayname);
    wachthond($extdebug,2, "########################################################################");

    $email_home_removed = NULL;
    $email_othr_removed = NULL;
    $email_work_removed = NULL;
    $email_priv_removed = NULL;

    wachthond($extdebug,4, 'org_email_home_email',  $email_home_email);
    wachthond($extdebug,4, 'new_email_home_email',  $new_email_home_email);
    wachthond($extdebug,4, 'new_email_othr_email',  $new_email_othr_email);
    wachthond($extdebug,4, 'new_email_work_email',  $new_email_work_email);     
    wachthond($extdebug,4, 'new_email_priv_email',  $new_email_priv_email);

    $array_emails = array(
        'displayname'                   =>  $displayname,
        'contact_id'                    =>  $ditevent_part_contact_id,
        'user_mail'                     =>  $user_mail,
        'email_prim_email'              =>  $email_prim_email,
        'email_home_email'              =>  $new_email_home_email,
        'email_othr_email'              =>  $new_email_othr_email,
        'email_work_email'              =>  $new_email_work_email,
        'email_priv_email'              =>  $new_email_priv_email,
        'email_plac_email'              =>  $new_email_plac_email,
        'email_onvr_email'              =>  $new_email_onvr_email,                
    );

    wachthond($extdebug,1, "########################################################################");
    wachthond($extdebug,1, "### EMAIL 7.0 RETURN ARRAY EMAILADRESSS",                   $array_emails);
    wachthond($extdebug,1, "########################################################################");

    return $array_emails;

}

function email_civicrm_update($contactid, $emailtype, $emailid, $oldemail, $newemail) {

    $extdebug           = 0;
    $apidebug           = FALSE;
    $extwrite           = 1;

    $contact_id         = $contactid;
    $email_type         = $emailtype;
    $email_id           = $emailid;
    $old_email          = $oldemail;
    $new_email          = $newemail;

    $email_removed      = false;

    if ($email_type == 'Other') {   $email_type_name = 'othr';      }
    if ($email_type == 'Gave')  {   $email_type_name = 'gave';      }
    if ($email_type == 'Home')  {   $email_type_name = 'home';      }
    if ($email_type == 'Work')  {   $email_type_name = 'work';      }
    if ($email_type == 'Kamp')  {   $email_type_name = 'kamp';      }
    if ($email_type == 'Prive') {   $email_type_name = 'priv';      }    

    if (empty($new_email)) {
        wachthond($extdebug,2, "email_$email_type_name niet van toepassing", "[$email_type_name]");
        return;
    }

    ##########################################################################################      
    ### CHECK OF WE EEN UPDATE MOETEN FORCEREN (BIJV. BIJ KAMP MAIL NOOIT ON_HOLD)
    ##########################################################################################      

    $force_update = false;

    if ($email_type == 'Kamp') {
        $force_update = true;
    }

    // Als de mail al klopt EN er is geen force update nodig, hoeven we niets te doen.
    // We geven dan direct het ID terug.
    if ($emailid && $oldemail === $newemail && $force_update === false) {
        return $emailid;
    }

    // --- STAP 1: AFHANDELING BESTAAND RECORD ---
    if ($email_id !== null && $email_id > 0) {

        ##########################################################################################      
        ### EMAIL UPDATE
        ##########################################################################################    

        // FIX: Update uitvoeren als email anders is OF als force_update aan staat
        if (($old_email !== $new_email OR $force_update === true)) {

            $api_values = [
                'email'                 =>  $new_email,
                'location_type_id:name' =>  $email_type,
                'is_primary'            =>  FALSE,
            ];

            // SPECIFIEK VOOR KAMP: FORCEER ON_HOLD OP FALSE (RESET BOUNCE/OPTOUT)
            if ($email_type == 'Kamp') {
                $api_values['on_hold']  = FALSE;
                wachthond($extdebug,2, "email_kamp on_hold status geforceerd naar", "FALSE");
            }

            $params_email_update = [
                'checkPermissions' => FALSE,
                'debug' => $apidebug,
                'where' => [
                    ['id',          '=', $email_id],
                    ['contact_id',  '=', $contact_id],
                ],
                'values' => $api_values,
            ];

            wachthond($extdebug,7, "params_email_$email_type_name_update",      $params_email_update);
            if ($extwrite == 1 AND !in_array($privacy_voorkeuren, array("33","44"))) {
                $result_email_update = civicrm_api4('Email', 'update', $params_email_update);
                wachthond($extdebug,9, "result_email_$email_type_name_update",  $result_email_update);
                wachthond($extdebug,1, "email_$email_type_name geupdated",      $new_email);
            }
        }
    }

    ##########################################################################################
    ### EMAIL CREATE
    ##########################################################################################

    // --- STAP 2: CREATE (Indien nieuw of verwijderd) ---
    if (($email_id === null || $email_id <= 0) || $email_removed === true) {

        $params_email_create = [
            'checkPermissions' => FALSE,
            'debug' => $apidebug,         
            'values' => [
                'contact_id'            => $contact_id,
                'email'                 => $new_email,
                'location_type_id:name' => $email_type,
            ],
        ];
        wachthond($extdebug,7, "params_email_$email_type_name_create",      $params_email_create);

        if ($extwrite == 1 AND !in_array($privacy_voorkeuren, array("33","44"))) {
            $result_email_create = civicrm_api4('Email', 'create', $params_email_create);
            wachthond($extdebug,9, "result_email_$email_type_name_create",  $result_email_create);
            wachthond($extdebug,2, "email_$email_type_name aangemaakt",     $new_email);

            return $result_email_create->first()['id']; // Geef de NIEUWE id terug aan het hoofdscript
        }
    }

    // M61: TODO MANIER VINDEN OM DE NIEUWE EMAIL_ID TE KUNNEN GEBRUIKEN
}

function email_civicrm_greeting($array_contditjaar, $ditjaar_array, $array_partditjaar = NULL) {

    $extdebug = 0;
    $apidebug = FALSE;

    if ( !is_array($array_contditjaar) OR !is_array($ditjaar_array) ) {
        return;
    }

    // OPTIMALISATIE: Gebruik de static cache functie
    $fiscal_data    = find_fiscalyear();
    $today_kampjaar = $fiscal_data['today_jaar'];
    
    wachthond($extdebug,4, 'today_kampjaar',    $today_kampjaar);

    wachthond($extdebug, 3, "########################################################################");
    wachthond($extdebug, 2, "### GREETING 0.1 GET VALUES FROM ARRAY",               "array_contditjaar");
    wachthond($extdebug, 2, "########################################################################");

    $contact_id  = $array_contditjaar['contact_id']  ?? NULL;
    $displayname = $array_contditjaar['displayname'] ?? NULL;
    $birth_date  = $array_contditjaar['birth_date']  ?? NULL;
    
    // Huidige waarden (om te checken of update nodig is)
    $current_greeting_id = $array_contditjaar['email_greeting_id']     ?? NULL;
    $current_style_id    = $array_contditjaar['communication_style_id'] ?? NULL;

    // 1. Haal CV data op (Gebruik NULL check om 0 te onderscheiden)
    $keren_leid  = $array_contditjaar['curcv_keer_leid'] ?? NULL;
    $keren_top   = $array_contditjaar['curcv_keer_top']  ?? NULL;

    // M61: Alleen de kostbare query doen als de waarden echt onbekend (NULL) zijn
    if ($keren_top === NULL || $keren_leid === NULL) {
        // Zorg dat cv_civicrm_configure óók geoptimaliseerd is met static caching!
        $array_cv   = cv_civicrm_configure($contact_id, $array_contditjaar, $ditjaar_array);
        $keren_leid = $array_cv['keren_leid'] ?? 0;
        $keren_top  = $array_cv['keren_top']  ?? 0;
        wachthond($extdebug, 3, 'Keren Leid/Top opgehaald via cv_configure', "Leid: $keren_leid | Top: $keren_top");
    }

    // Cast naar int voor veilige vergelijkingen
    $keren_leid = (int)$keren_leid;
    $keren_top  = (int)$keren_top;

    wachthond($extdebug, 3, 'keren_leid', $keren_leid);
    wachthond($extdebug, 3, 'keren_top',  $keren_top);

    // 2. Leeftijd verwerking (Geoptimaliseerd met Static Cache)
    $leeftijd_nextkamp_rondjaren = 0;

    if (!empty($birth_date)) {
        // We halen de 'volgend kamp' startdatum op uit de geoptimaliseerde cache
        $today_lastnext             = find_lastnext(date("Y-m-d"));
        $today_nextkamp_start_date  = $today_lastnext['next_start_date'] ?? NULL;

        if ($today_nextkamp_start_date) {
            // Gebruik de nieuwe snelle diff functie
            $leeftijd_data = leeftijd_civicrm_diff('greeting_calc', $birth_date, $today_nextkamp_start_date);
            $leeftijd_nextkamp_rondjaren = $leeftijd_data['leeftijd_rondjaren'] ?? 0;
        }
    }

    wachthond($extdebug, 3, 'birth_date', $birth_date);
    wachthond($extdebug, 3, 'leeftijd_nextkamp_rondjaren', $leeftijd_nextkamp_rondjaren);

    wachthond($extdebug,3, "########################################################################");
    wachthond($extdebug,2, "### GREETING 0.2 GET VALUES FROM ARRAY",                   "ditjaar_array");
    wachthond($extdebug,2, "########################################################################");

    $ditjaardeelyes      = $ditjaar_array['diteventdeelyes'] ?? 0;
    $ditjaardeelnot      = $ditjaar_array['diteventdeelnot'] ?? 0;
    $ditjaardeelmss      = $ditjaar_array['diteventdeelmss'] ?? 0;
    $ditjaardeeltop      = $ditjaar_array['diteventdeeltop'] ?? 0;

    $ditjaarleidyes      = $ditjaar_array['diteventleidyes'] ?? 0;
    $ditjaarleidnot      = $ditjaar_array['diteventleidnot'] ?? 0;
    $ditjaarleidmss      = $ditjaar_array['diteventleidmss'] ?? 0;

    wachthond($extdebug,3, 'ditjaardeelyes',    $ditjaardeelyes);
    wachthond($extdebug,3, 'ditjaardeelnot',    $ditjaardeelnot);
    wachthond($extdebug,3, 'ditjaardeelmss',    $ditjaardeelmss);
    wachthond($extdebug,3, 'ditjaarleidyes',    $ditjaarleidyes);
    wachthond($extdebug,3, 'ditjaarleidnot',    $ditjaarleidnot);
    wachthond($extdebug,3, 'ditjaarleidmss',    $ditjaarleidmss);    

    wachthond($extdebug,3, "########################################################################");
    wachthond($extdebug,2, "### GREETING 0.3 GET VALUES FROM ARRAY",              "array_partditjaar",);
    wachthond($extdebug,2, "########################################################################");

    $ditjaar_event_kampnaam                  = $array_partditjaar['kenmerken_kampnaam']                  ?? NULL;
    $ditjaar_event_kampkort                  = $array_partditjaar['kenmerken_kampkort']                  ?? NULL;
    $ditjaar_part_kampnaam                   = $array_partditjaar['part_kampnaam']                       ?? NULL;
    $ditjaar_part_kampkort                   = $array_partditjaar['part_kampkort']                       ?? NULL;

    wachthond($extdebug,3, 'ditjaar_event_kampnaam',                 $ditjaar_event_kampnaam);
    wachthond($extdebug,3, 'ditjaar_event_kampkort',                 $ditjaar_event_kampkort);
    wachthond($extdebug,3, 'ditjaar_part_kampnaam',                  $ditjaar_part_kampnaam);
    wachthond($extdebug,3, 'ditjaar_part_kampkort',                  $ditjaar_part_kampkort);

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,1, "### GREETING 1.0 DEFINE POSTAL & EMAIL GREETING", "PROCESSING");
    wachthond($extdebug,2, "########################################################################");

    // --- PRIORITEITSBEPALING (Ladder-optimalisatie) ---
    $reason = "Default fallback";
    
    if ($leeftijd_nextkamp_rondjaren >= 60) {
        // 1. Ouderen krijgen altijd formele stijl
        $email_greeting_id      = 1; // Hallo Voornaam
        $communication_style_id = 'formal';
        $reason                 = "Leeftijd >= 60 (Formeel)";
    } 
    elseif ($keren_top >= 1 || $keren_leid >= 1 || $ditjaardeeltop == 1 || $leeftijd_nextkamp_rondjaren >= 18) {
        // 2. Volwassenen, Leiding en Top-deelnemers (oud of nieuw) zijn altijd informeel
        $email_greeting_id      = 1; // Hallo Voornaam
        $communication_style_id = 'familiar';
        $reason                 = "Volwassene/Leiding/Top (Informeel)";
    } 
    elseif ($ditjaardeelyes == 1 || $ditjaardeelmss == 1 || $leeftijd_nextkamp_rondjaren < 18) {
        // 3. Minderjarige deelnemers krijgen Begroeting 2 (Ouder/Verzorger) en Formele stijl
        $email_greeting_id      = 2; // Ouder/Verzorger van
        $communication_style_id = 'formal';
        $reason                 = "Minderjarige deelnemer (Ouder/Verzorger + Formeel)";
    } 
    else {
        // Fallback
        $email_greeting_id      = 1;
        $communication_style_id = 'familiar';
    }

    wachthond($extdebug, 1, "BESLUIT BEGROETING", "ID: $email_greeting_id | Stijl: $communication_style_id | Reden: $reason");

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,1, "### 6.0 UPDATE CONTACT",                              $displayname);
    wachthond($extdebug,2, "########################################################################");

    // PERFORMANCE: Update alleen als er iets verandert
    $needs_update = false;
    if ($current_greeting_id != $email_greeting_id) $needs_update = true;
    if ($current_style_id != $communication_style_id) $needs_update = true;

    if ($contact_id && $needs_update)  {
        $params_contact = [
            'checkPermissions'  =>  FALSE,
            'reload'            =>  FALSE, // Voorkomt herladen van data na save (Snelheid!)
            'debug'             =>  $apidebug,
            'where'             => [['id', '=', $contact_id]],
            'values'            => [
                'email_greeting_id'             => $email_greeting_id,
                'communication_style_id:name'   => $communication_style_id,
            ],
        ];

        wachthond($extdebug, 7, 'params_contact_update', $params_contact);

        try {
            $result_contact = civicrm_api4('Contact', 'update', $params_contact);
            wachthond($extdebug, 9, 'result_contact', $result_contact);
            wachthond($extdebug, 2, "UPDATE greeting and style executed for $displayname", "[SUCCESS]");
        } catch (\Exception $e) {
            wachthond($extdebug, 1, "Fout bij bijwerken contact $contact_id", $e->getMessage());
        }
    } else {
        wachthond($extdebug, 2, "UPDATE SKIPPED", "Geen wijziging in greeting/style nodig.");
    }

    $array_greeting = array(
        'email_greeting_id'             =>  $email_greeting_id,
        'communication_style_id'        =>  $communication_style_id,
    );

    wachthond($extdebug, 2, 'RETURN array_greeting',         $array_greeting);

    return $array_greeting;
}

/**
 * Implements hook_civicrm_post().
 *
 * Wordt uitgevoerd NADAT een object is opgeslagen.
 * Trigger bij wijziging van Email OF Contact (Save knop in uw screenshot).
 */
function email_civicrm_post($op, $objectName, $objectId, &$objectRef) {

    $extdebug           = 0;

    // Check 1: We reageren op 'Email' (direct) EN 'Contact' (via het scherm in uw screenshot)
    if (!in_array($objectName, ['Email', 'Contact'])) {
        return;
    }

    // Check 2: We reageren alleen op aanmaken of bewerken
    if (!in_array($op, ['create', 'edit'])) {
        return;
    }

    // Check 3: Voorkom oneindige loops en dubbele uitvoering
    // Als u een contact opslaat, vuurt CiviCRM vaak hooks voor Contact EN Email af. 
    // Deze static variabele zorgt dat we het script maar 1x per request draaien.
    static $processing = false;
    
    if ($processing) {
        return;
    }

    // Zet vlag aan
    $processing = true;

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,1, "### EMAIL HOOK POST TRIGGERED ($op)",                   "$objectName ID: $objectId");
    wachthond($extdebug,2, "########################################################################");

    try {
        $contact_id = NULL;

        // Situatie A: Het is een Contact update (Uw screenshot situatie)
        if ($objectName == 'Contact') {
            $contact_id = $objectId;
        }
        // Situatie B: Het is een directe Email update
        elseif ($objectName == 'Email') {
            $contact_id = $objectRef->contact_id ?? NULL;
            
            // Fallback als contact_id mist in objectRef
            if (empty($contact_id)) {
                $email_record = civicrm_api4('Email', 'get', [
                    'checkPermissions' => FALSE,
                    'select' => ['contact_id'],
                    'where' => [['id', '=', $objectId]],
                ])->first();
                $contact_id = $email_record['contact_id'] ?? NULL;
            }
        }

        if ($contact_id) {
            
            // ##########################################################################################
            // ### DATA OPHALEN (ESSENTIEEL: HIER UW EIGEN FUNCTIE PLAATSEN)
            // ##########################################################################################
            // U moet hier uw logica toevoegen om de arrays op te bouwen voor dit contact.
            // Voorbeeld: $data = mijn_custom_data_functie($contact_id);
            
            $array_contditjaar      = NULL; // TODO: Vul met: get_cont_data($contact_id)
            $ditjaar_array          = NULL; // TODO: Vul met: get_ditjaar_data(...)
            $array_partditjaar      = NULL; // TODO: Vul met: get_part_data(...)

            // TIJDELIJKE VEILIGHEID: Alleen uitvoeren als u de data functie heeft gekoppeld
            if (is_array($array_contditjaar)) {
                
                wachthond($extdebug,1, "### RUNNING CONFIGURE FROM HOOK FOR", $contact_id);
                
                // Voer de configuratie uit
                email_civicrm_configure($array_contditjaar, $ditjaar_array, $array_partditjaar);
                
            } else {
                wachthond($extdebug,1, "### SKIPPING CONFIGURE", "Hook mist data-ophaal functie (zie code commentaar)");
            }
        }

    } catch (\Exception $e) {
        wachthond($extdebug,1, "### ERROR IN EMAIL HOOK", $e->getMessage());
    }

    // Zet vlag uit
    $processing = false;
}