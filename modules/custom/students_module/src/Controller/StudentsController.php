<?php

namespace Drupal\students_module\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use http\Exception;
use Drupal\Core\Controller\ControllerBase;

class StudentsController extends ControllerBase {

  public function showStudents() {
    try {
      $conn = Database::getConnection();
      $studentsList = $conn->select('students_data', 's')
        ->fields('s')
        ->execute()
        ->fetchAll();

      $rows = [];
      foreach ($studentsList as $student) {
        $edit = Url::fromUserInput('/students/form/data?id=' . $student->id);
        $deleteLink = Link::createFromRoute(t('Delete'), 'students_module.delete_student', ['id' => $student->id], [
          'attributes' => [
            'class' => [
              'use-ajax',
            ],
          ],
        ])->toString();
        $row = [
          'id' => $student->id,
          'name' => $student->name,
          'gender' => $student->gender,
          'faculty_number' => $student->faculty_number,
          'editLink' => \Drupal::currentUser()
            ->hasPermission('edit students content') ? Link::fromTextAndUrl($this->t('Edit'), $edit) : '',
          'deleteLink' => \Drupal::currentUser()
            ->hasPermission('delete students content') ? $deleteLink : '',
        ];

        if ($row['id']) {
          $rows[] = [
            'data' => $row,
            'class' => ['student-row-' . $row['id']],
          ];
        }

      }
      $header = [
        ['data' => t('Id')],
        ['data' => t('Name')],
        ['data' => t('Gender')],
        ['data' => t('Faculty number')],
        ['data' => t('Edit Option')],
        ['data' => t('Delete Option')],
      ];

      $studentsTable['table'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No users found'),
        '#attributes' => [
          'id' => 'students_table',
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'draggable-weight',
          ],
        ],
      ];

      return $studentsTable;

    } catch (Exception $ex) {
      \Drupal::logger('dn_students')->error($ex->getMessage());
    }
  }


  public function deleteStudent($id): AjaxResponse {
    $student_id = $id;
    \Drupal::database()
      ->delete('students_data')
      ->condition('id', $student_id)
      ->execute();
    $response = new AjaxResponse();
    $response->addCommand(new MessageCommand(t('Student deleted successfully'), '.student-list-status-wrapper'));
    $response->addCommand(new InvokeCommand('.student-row-' . $student_id, 'remove'));
    return ($response);
  }


}
