<?php


namespace Drupal\students_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use http\Exception;


class StudentForm extends FormBase {


  public function getFormId() {
    return 'student_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $conn = Database::getConnection();
    if (isset($_GET['id'])) {
      $query = $conn->select('students_data', 's')
        ->condition('id', $_GET['id'])
        ->fields('s');
      $record = $query->execute()->fetchAssoc();
    }
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#maxlength' => 100,
      '#default_value' => (isset($record['name']) && $_GET['id']) ? $record['name'] : '',
    ];
    $form['gender'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gender'),
      '#required' => TRUE,
      '#default_value' => (isset($record['gender']) && $_GET['id']) ? $record['gender'] : '',
      '#options' => [
        'male' => $this
          ->t('Male'),
        'female' => $this
          ->t('Female'),
      ],
    ];
    $form['faculty_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Faculty Number'),
      '#required' => TRUE,
      '#maxlength' => 8,
      '#default_value' => (isset($record['faculty_number']) && $_GET['id']) ? $record['faculty_number'] : '',
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#default_value' => $this->t('Save'),
    ];

    $form['actions']['reset'] = [
      '#type' => 'button',
      '#button_type' => 'primary',
      '#default_value' => $this->t('Reset'),
      '#attributes' => ['onclick' => 'this.form.reset();return false;'],
    ];

    $form['#theme'] = 'students_add_form';

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $field = $form_state->getValues();
    $fields["name"] = $field['name'];
    if (!$form_state->getValue('name') || empty($form_state->getValue('name'))) {
      $form_state->setErrorByName('name', $this->t('Provide Name'));
    }
    if (strlen($form_state->getValue('faculty_number')) > 8) {
      $form_state->setErrorByName('faculty_number',
        $this->t('Student`s faculty number must contain 8 or less digits'));
    }

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $re_url = Url::fromRoute('students_module.students_list');
    try {
      $conn = Database::getConnection();

      $field = $form_state->getValues();

      $name = $field['name'];
      $gender = $field['gender'];
      $faculty_number = $field['faculty_number'];

      $field = [
        'name' => $name,
        'gender' => $gender,
        'faculty_number' => $faculty_number,
      ];
      if (isset($_GET['id'])) {
        $conn->update('students_data')
          ->fields($field)
          ->condition('id', $_GET['id'])
          ->execute();
        $this->messenger()->addMessage('Successfully Updated Record');
      }
      else {
        $conn->insert('students_data')
          ->fields($field)->execute();
        $this->messenger()
          ->addMessage($this->t('The Student data has been successfully saved'));
        $form_state->setRedirectUrl($re_url);
      }
    } catch (Exception $ex) {
      \Drupal::logger('dn_students')->error($ex->getMessage());
    }

  }

}

