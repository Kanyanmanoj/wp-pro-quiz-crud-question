<?php
if (!class_exists('WP_List_Table')) {
  require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Duplicate_Question_List_Table extends WP_List_Table
{
  private $post_data;
  function __construct($post_data)
  {
    parent::__construct(array(
      'singular' => 'question',
      'plural'   => 'questions',
      'ajax'     => false,
    ));
    $item = '';
    $this->post_data = $post_data;
  }

  function column_default($item, $column_name)
  {
    return $item[$column_name];
  }

  function get_columns()
  {
    $columns = array(
      'question_title'  => 'Question Title',
      'category' => 'Category',
      'quiz' => 'Quiz',
    );
    return $columns;
  }

  function prepare_items()
  {
    global $wpdb;

    $per_page = 10;
    $current_page = $this->get_pagenum();
    $selected_quiz = $this->post_data;
    $sql = "
        SELECT COUNT(*)
        FROM {$wpdb->prefix}wp_pro_quiz_question q
        WHERE (
            SELECT COUNT(*)
            FROM {$wpdb->prefix}wp_pro_quiz_question q2
            WHERE q.question = q2.question
            AND q.answer_data = q2.answer_data
        ) > 1";
    if (!empty($selected_quiz['quizzes'])) {
      $sql .= " AND q.quiz_id IN (" . implode(',', array_map('intval', $selected_quiz['quizzes'])) . ")";
    }
    $sql .= " ORDER BY q.question ASC";
    $total_items = $wpdb->get_var($sql);

    $this->set_pagination_args(array(
      //'total_items' => $total_items,
      'total_items' => $total_items,
      'per_page'    => $per_page,
    ));

    $columns = $this->get_columns();
    $hidden = array();
    $sortable = array();
    $this->_column_headers = array($columns, $hidden, $sortable);

    $selected_quiz = $this->post_data;
    $sql = "
        SELECT q.id, q.question, q.quiz_id, c.category_name, m.name 
        FROM {$wpdb->prefix}wp_pro_quiz_question q
        LEFT JOIN {$wpdb->prefix}wp_pro_quiz_category c ON q.category_id = c.category_id
        LEFT JOIN {$wpdb->prefix}wp_pro_quiz_master m ON q.quiz_id = m.id
        WHERE (
            SELECT COUNT(*)
            FROM {$wpdb->prefix}wp_pro_quiz_question q2
            WHERE q.question = q2.question
            AND q.answer_data = q2.answer_data
        ) > 1";
    if (!empty($selected_quiz['quizzes'])) {
      $sql .= " AND q.quiz_id IN (" . implode(',', array_map('intval', $selected_quiz['quizzes'])) . ")";
    }
    $sql .= " ORDER BY q.question ASC LIMIT " . ($current_page - 1) * $per_page . ", $per_page";
    $this->items = $wpdb->get_results($sql, ARRAY_A);
  }

  function column_question_title($item)
  {
    $actions = array(
      'edit'   => sprintf('<a href="%s">Edit</a>', '?page=wpProQuiz&module=question&action=addEdit&quiz_id=' . $item['quiz_id'] . '&questionId=' . $item['id']),
      'delete' => sprintf('<a href="#" class="delete-question" data-question-id="%s">Delete</a>', $item['id']),
    );

    return sprintf('%1$s %2$s', $item['question'], $this->row_actions($actions));
  }
  function column_category($item)
  {
    return $item['category_name'];
  }

  function column_quiz($item)
  {

    return $item['name'];
  }
  function column_cb($item)
  {
    return sprintf('<input type="checkbox" name="question[]" value="%s" />', $item['id']);
  }
}

function duplicate_question_plugin_page()
{

?>
  <div class="wrap">
    <h1>Duplicate Questions List</h1>
    <?php render_question_form('duplicate-questions');
    if (isset($_GET['quizzes'])) {
      $question_list_table = new Duplicate_Question_List_Table($_GET);
      $question_list_table->prepare_items();
    ?>
      <form method="post">
        <input type="hidden" name="page" value="duplicate-question-plugin" />
        <?php $question_list_table->display();
        ?>
      </form>
  </div>
  <script>
    jQuery(document).ready(function($) {
      $('.delete-question').on('click', function(e) {
        e.preventDefault();
        var questionId = $(this).data('question-id');
        var confirmDelete = confirm('Are you sure you want to delete this question?');
        if (confirmDelete) {
          // Make an AJAX request to delete the question
          $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
              action: 'delete_dup_question',
              question_id: questionId,
            },
            success: function(response) {
              // Handle the response as needed
              console.log(response);
              location.reload();
            },
          });
        }
      });
    });
  </script>
<?php
    }
  }


  add_action('wp_ajax_delete_dup_question', 'delete_dup_question_callback');

  function delete_dup_question_callback()
  {
    $questionId = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;

    if ($questionId) {
      global $wpdb;
      $wpdb->delete($wpdb->prefix . 'wp_pro_quiz_question', array('id' => $questionId));
      echo 'Question deleted successfully.';
    } else {
      echo 'Invalid question ID.';
    }

    wp_die(); // This is required to terminate immediately and return a proper response.
  }
