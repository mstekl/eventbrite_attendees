<?php
namespace Drupal\eventbrite_attendees;

class UsersSync
{

    protected $api_client;
    protected $config;

    function __construct()
    {
        $this->api_client = \Drupal::service('eventbrite_attendees.api_client');
        $this->config = \Drupal::service('config.factory')->getEditable('eventbrite_attendees.settings');
    }



    public function getNewAttendees($event_id, $last_seen_eventbrite_id = null)
    {

        $a = $this->api_client->getEventAttendees($event_id, ['last_item_seen' => $last_seen_eventbrite_id]);

        return $a;
    }

    public function getAllAttendees($event_id)
    {
        return $this->api_client->getEventAttendees($event_id);
    }

    public function createUsersFromAttendees($attendees)
    {

        $return = [
            'errors' => [],
            'users' => [],
            'duplicated' => []
        ];

        $processed = [];

        $last_id = '';

        foreach ($attendees as $a) {

                $username = $a['profile']['email'];

                $existing_user = user_load_by_mail($username);
                if(!empty($existing_user)){
                    $user = \Drupal\user\Entity\User::load($existing_user->id());
                    $action = 'Edit';
                }else{
                    $user = \Drupal\user\Entity\User::create();
                    $action = 'New';
                    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

                    $user->setPassword(md5('imcs'.$username));
                    $user->enforceIsNew();
                    $user->setEmail($username);
                    $user->setUsername($username); //This username must be unique and accept only a-Z,0-9, - _ @ .
                    $user->set('langcode', $language);
                    $user->set('preferred_langcode', $language);
                    $user->set('mail', $username);
                }

                $user->set('field_first_name', $a['profile']['first_name']);
                $user->set('field_last_name', $a['profile']['last_name']);
                $user->set('field_company', isset($a['profile']['company'])?$a['profile']['company']:'');
                $user->set('field_job_title', isset($a['profile']['job_title'])?$a['profile']['job_title']:'');
                $user->set('field_eventbrite_id', $a['id']);
                // save the last ID of eventbrite to query from there later
                $last_id = $a['id'] > $last_id ? $a['id'] : $last_id;

                $user->addRole('attendee');
                $user->activate();

                try{
                    $violations = $user->validate();
                    if($user->save()){
                        $return['users'][$a['id']] = [ $action => $user->id().' - '.$username];
                        if(in_array($username, $processed)){
                            array_push($return['duplicated'], $username);
                        }else {
                            array_push($processed, $username);
                        }
                    }else{
                        $return['errors'][$a['id']] = $violations;
                    }

                }catch(Exception $e){
                    $return['errors'][$a['id']] = $e->getMessage();
                }


        }

        $this->config->set('last_attendee_id', $last_id)->save();
        var_dump('last_attendee_id', $last_id);
        return $return;
    }

}