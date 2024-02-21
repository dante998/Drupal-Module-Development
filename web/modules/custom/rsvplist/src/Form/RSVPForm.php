<?php

/**
 * @file
 * A form to collect an email adress for RSVP details.
 */

 namespace Drupal\rsvplist\Form;

 use Drupal\Core\Form\FormBase;
 use Drupal\Core\Form\FormStateInterface;

 class RSVPForm extends FormBase {

    /**
     * {@inheritDoc}
     */
    public function getFormId() {
        return 'rsvplist_email_form';
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Attempt to get the fully loaded node object of the viewed page.
        $node = \Drupal::routeMatch()->getParameter('node');

        // Some pages may not be nodes though and $node will be NULL on these pages.
        // If node was loaded, get the node id.
        if ( !(is_null($node))) {
            $nid = $node->id();
        } else {
            // If a node could not be loaded, default to 0.
            $nid = 0;
        }
        
        // Establish the $form render array. It has an email text field,
        // a submit button and a hidden field containing the node ID.
        $form['email'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Email address'),
            '#size' => 25,
            '#description' => $this->t("We will send updates to the email adress you provide."),
            '#required' => TRUE,
        ];
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Enter'),
        ];
        $form['nid'] = [
            '#type' => 'hidden',
            '#value' => $nid,
        ];
        return $form;
     }

     /**
      * {@inheritDoc}
      */
      public function validateForm(array &$form, FormStateInterface $form_state) {
        $value = $form_state->getValue('email');
        if (! (\Drupal::service('email.validator')->isValid($value))) {
            $form_state->setErrorByName('email', $this->t('It appears that %mail is not valid email. Please try again.', 
            ['%mail' => $value]));
        }
      }

     /**
      * {@inheritDoc}
      */
     public function submitForm(array &$form, FormStateInterface $form_state) {
        try {
            // Phase 1: Initiate variables to save.
            // Get current user ID.
            $uid = \Drupal::currentUser()->id();

            // How to load a full user object of the current user.
            // It is shown for demonstration purposes only.
            $full_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

            // Obtain values as entered into the Form.
            $nid = $form_state->getValue('nid');
            $email = $form_state->getValue('email');

            // Current time.
            $current_time = \Drupal::time()->getRequestTime();

            // Phase 2: Save the values to the database.
            // Start to build a query builder object $query.
            $query = \Drupal::database()->insert('rsvplist');

            // Specify the fields that the query will insert into.
            $query->fields([
                'nid',
                'uid',
                'mail',
                'created',
             ]);

            // Set the values of the fields we selected.
            // Note that they must be at the same order as we defined them in the $query->fields([...]) above.
            $query->values([
                $nid,
                $uid,
                $email,
                $current_time,
            ]); 

            // Execute the query.
            // Drupal handles the exact syntax of the query automatically.
            $query->execute();

            // Phase 3: Display a success message.
            \Drupal::messenger()->addMessage($this->t(
              'Successfull! Thank you for your RSVP, you are on the list for the event!'
            ));

        } catch (\Exception $e) {
            \Drupal::messenger()->addError($this->t(
              'Unable to save RSVP settings at this time due to database error.
               Please try again.'
            ));
        }
       
        // $submitted_email = $form_state->getValue('email');
       // $this->messenger()->addMessage($this->t("The form is working! You entered @entry.", ['@entry' => $submitted_email]));
       
     }
 }