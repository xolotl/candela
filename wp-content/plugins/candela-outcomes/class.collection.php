<?php
namespace Candela\Outcomes;

include_once( __DIR__ . '/class.base.php' );

/**
 * Collection Class
 */
class Collection extends Base {
  public static $type = 'collection';
  public $status = 'private';

  public function load( $uuid ) {
    $item = load_item_by_uuid( $uuid, 'collection' );

    if ( ! empty( $item ) ) {
      foreach ($item as $prop => $value ) {
        $this->$prop = $value;
      }

      $this->uuid = $uuid;
    }
    else {
      unset( $this->uuid );
      $this->errors['loader']['notfound'] = __('Unable to find item with UUID.', 'candela_outcomes' );
    }
  }

  public function save() {
    global $wpdb;
    $table = $wpdb->prefix . 'outcomes_collection';

    $data = array(
      'uuid' => $this->uuid,
      'user_id' => $this->user_id,
      'title' => $this->title,
      'description' => $this->description,
      'status' => $this->status
    );

    $format = array(
      '%s',
      '%d',
      '%s',
      '%s',
      '%s',
    );

    // Replace does not work properly (probably due to lack of auto increment).
    $exists_sql = $wpdb->prepare('SELECT uuid FROM ' . $table . ' WHERE uuid=%s', $this->uuid );
    $exists = $wpdb->get_col($exists_sql);
    if ( empty( $exists ) ) {
      $wpdb->insert( $table, $data, $format );
    }
    else {
      $where = array('uuid' => $this->uuid );
      $wpdb->update( $table, $data, $where, $format);
    }

  }

  public function form() {
    $this->formHeader();

    $status = new Select();
    $status->id = 'status';
    $status->name = 'status';
    $status->label = __( 'Status' );
    $status->options = $this->getStatusOptions();
    $status->value = $this->status;
    $status->formElement();

    $this->formFooter();
  }

  public function getStatusOptions() {
    return array(
      'private' => __('Private', 'candela_outcomes'),
      'public' => __('Public', 'candela_outcomes'),
    );
  }

  public function uri( $edit = FALSE ) {
    if ( $edit ) {
      return home_url() . '/wp-admin/admin.php?page=edit_collection&uuid=' . $this->uuid;
    }

    return home_url() . '/outcomes/collection/' . $this->uuid;
  }

  public function processForm() {
    // Only process if valid nonce.
    if ( empty( $this->errors) ) {
      if ( ! empty ($_POST['uuid'] ) && empty( $this->uuid )) {
        $this->uuid = $_POST['uuid'];
      }
      else {
        // new collection
        $this->uuid = get_uuid();
      }

      $this->title = empty( $_POST['title']) ? '' : $_POST['title'];
      $this->description = empty( $_POST['description'] ) ? '' : $_POST['description'];
      $this->status = empty( $_POST['status'] ) ? '' : $_POST['status'];
      $this->user_id = get_current_user_id();


      $this->validate();

      if ( empty ( $this->errors ) ) {
        $this->save();
        wp_redirect( $this->uri() );
        exit;
      }
    }
  }
}