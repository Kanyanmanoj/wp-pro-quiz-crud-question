<?php
/*
Plugin Name: Wp Pro Quiz Crud Question
Description: A WordPress plugin to get all questions list that have missing "Message with the correct answer". For support or inquiries, contact the author at: manojkanyan1@gmail.com
Version: 1.0
Author: Manoj Kumar
Author URI: https://github.com/Kanyanmanoj
Plugin URI: https://github.com/Kanyanmanoj/wp-pro-quiz-crud-question
*/

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}
if (is_admin()) {
  require_once plugin_dir_path(__FILE__) . 'includes/Custom_Question_List_Table.php';
  require_once plugin_dir_path(__FILE__) . 'includes/Duplicate_Question_List_Table.php';
}

// Hook to add admin menu page
add_action('admin_menu', 'custom_question_plugin_menu');

function custom_question_plugin_menu()
{
  add_menu_page(
    'Questions List',
    'Questions List',
    'manage_options',
    'custom-question-plugin',
    'custom_question_plugin_page'
  );
}

// Add the sub-menu page
add_action('admin_menu', 'add_duplicate_question_submenu');

function add_duplicate_question_submenu()
{

  add_submenu_page(
    'custom-question-plugin',
    'Questions Count',
    'Questions Count',
    'manage_options',
    'total-duplicate-questions',
    'get_total_duplicates'
  );
  add_submenu_page(
    'custom-question-plugin',
    'Duplicates List',
    'Duplicates List',
    'manage_options',
    'duplicate-questions',
    'duplicate_question_plugin_page'
  );
}
function get_total_duplicates()
{
  global $wpdb;
  $total_items = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}wp_pro_quiz_question WHERE correct_msg = ''");
  echo "<h1>Total Number of Empty Message Questions:- " . $total_items . "</h1>";
  exit();
}

function enqueue_select2_script()
{
  // Enqueue Select2 script
  wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);

  // Enqueue Select2 stylesheet
  wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
}

add_action('admin_enqueue_scripts', 'enqueue_select2_script');

function custom_select2_styles()
{
  echo '<style>
      /* Increase the width of the dropdown to 100% */
      .select2-container {
          width: 100% !important;
      }

      /* Adjust the font size and color */
      .select2-selection__rendered {
          font-size: 14px;
          color: #333;
      }

      /* Adjust the background and border color on hover */
      .select2-container--default.select2-container--open .select2-selection--multiple {
          background-color: #f8f8f8;
          border-color: #ddd;
      }

      /* Adjust the font size and color of options in the dropdown */
      .select2-results__option {
          font-size: 14px;
          color: #333;
      }

      label {
        font-weight: bold;
        margin-bottom: 10px;
        display: block;
    }
    .search-btn {
      padding: 5px 10px;
      background: green;
      color: #fff;
      border: 1px solid;
      border-radius: 4px;
      margin-top: 10px;
      cursor: pointer;
  }
    a.select-all {
      background: #135e96;
      color: #fff;
      padding: 5px 10px;
      display: inline;
      border-radius: 3px;
      margin-top: 10px;
      cursor: pointer;
  }
  a.deselect-all {
    background: #ce4444;
    color: #fff;
    padding: 5px 10px;
    display: inline;
    border-radius: 3px;
    margin-top: 10px;
    cursor: pointer;
  }
  </style>';
}

add_action('admin_head', 'custom_select2_styles');

function render_question_form($page)
{
?>
  <form method="get" action="<?= admin_url('/admin.php') ?>">
    <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>" />
    <label for="quizzes">Select Quizzes:</label>
    <select name="quizzes[]" id="quizzes" multiple>
      <!-- Populate the dropdown with quiz options -->
      <?php
      $quizzes = get_quizzes();
      foreach ($quizzes as $quiz) {
        echo "<option value='{$quiz->id}'>{$quiz->name}</option>";
      }
      ?>
    </select>
    <button type="submit" class="search-btn" name="search">Search</button>
  </form>
  <script>
    jQuery(document).ready(function($) {
      $('#quizzes').select2({
        minimumResultsForSearch: 20
      });

      $('#quizzes').on('select2:opening select2:closing', function(event) {
        var $searchfield = $(this).parent().find('.select2-search__field');
        $searchfield.prop('disabled', false);
      });

      // Add "Select All" and "Deselect All" buttons
      $('.select2-container').append('<a class="select-all">Select All</a><a class="deselect-all">Deselect All</a>');

      // Handle "Select All" button click
      $('.select-all').on('click', function() {
        $('#quizzes option').prop('selected', true);
        $('#quizzes').trigger('change.select2');
      });

      // Handle "Deselect All" button click
      $('.deselect-all').on('click', function() {
        $('#quizzes option').prop('selected', false);
        $('#quizzes').trigger('change.select2');
      });

    });
  </script>
<?php
}

function get_quizzes()
{
  global $wpdb;
  $data = $wpdb->get_results(
    "SELECT id,name
    FROM {$wpdb->prefix}wp_pro_quiz_master"
  );
  return $data;
}
