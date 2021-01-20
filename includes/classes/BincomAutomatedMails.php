<?php


class BincomAutomatedMails
{
    const post_type = 'bma-mail-group';
    const default_status = 'publish';
    const input_to_check_meta_name = '_input_to_check';
    const form_to_check_slug_meta_name = '_form_to_check';
    const  templates_type = 'single';


    private static $found_items = 0;

    private $id;
    public $name; //description of mail
    public $title;  //mail name
    public $content; //multiple or single
    public $input_to_check;
    public $form_to_check_slug;
    public $status;
    private $timestamp = null;

    public static  function register_post_type(){

        register_post_type( self::post_type, array(
            'labels' => array(
                'name' => __( 'Bincom Automated Mails', 'bma' ),
                'singular_name' => __( 'Bincom Automated Mail', 'bma' ),
            ),
            'rewrite' => true,
            'query_var' => false,
        ) );
    }

    public  static  function add($args = ''){
        $args = wp_parse_args( $args, array(
            'status' => '',
            'name' => '',
            'title' => '',
            'content' => array(),
            'timestamp' => null,
            'input_to_check' => '',
            'form_to_check_slug' => ''
        ) );
        $obj = new self();

        $obj->title = $args['title'];
        $obj->status = $args['status'];
        $obj->name = $args['name'];
        $obj->content = $args['content'];
        $obj->input_to_check = $args['input_to_check'];
        $obj->form_to_check_slug = $args['form_to_check_slug'];
        if ( $args['timestamp'] ) {
            $obj->timestamp = $args['timestamp'];
        }
        $obj->save();
        return $obj;

    }
    public  static  function update($id,$args = ''){
        $args = wp_parse_args( $args, array(
            'ID' => $id,
            'status' => '',
            'name' => '',
            'title' => '',
            'content' => array(),
            'timestamp' => null,
            'input_to_check' => '',
            'form_to_check_slug' => ''
        ) );
        $obj = new self();
        $obj->title = $args['title'];
        $obj->status = $args['status'];
        $obj->name = $args['name'];
        $obj->content = $args['content'];
        $obj->input_to_check = $args['input_to_check'];
        $obj->form_to_check_slug = $args['form_to_check_slug'];
        if ( $args['timestamp'] ) {
            $obj->timestamp = $args['timestamp'];
        }
        $obj->updatePost();
        return $obj;

    }

    public static function count( $args = '' ) {
        if ( $args ) {
            $args = wp_parse_args( $args, array(
                'offset' => 0,
            ) );

            self::find( $args );
        }

        return absint( self::$found_items );
    }
    public static function find( $args = '' ) {
        $defaults = array(
//            'posts_per_page' => 10,
            'offset' => 0,
            'orderby' => 'ID',
            'order' => 'DESC',
            'meta_key' => '',
            'meta_value' => '',
            'post_name' => '',
            'post_status' => 'any',
        );
        $args = wp_parse_args( $args, $defaults );

        $args['post_type'] = self::post_type;
        $q = new WP_Query();
        $posts = $q->query( $args );

        self::$found_items = $q->found_posts;

        $objs = array();

        foreach ( (array) $posts as $post ) {
            $objs[] = new self( $post );
        }

        return $objs;

    }

    public function __construct( $post = null ) {
        if ( ! empty( $post ) and $post = get_post( $post ) ) {
            $this->id = $post->ID;
            $this->content = $this->content_unserialize($post->post_content );
            $this->title = $post->post_title;
            $this->timestamp = $post->post_date_gmt;
            $this->status = $post->post_status;
            $this->name = $post->post_name;
            $this->input_to_check = $this->getPostMeta($post->ID,self::input_to_check_meta_name);
            $this->form_to_check_slug = $this->getPostMeta($post->ID,self::form_to_check_slug_meta_name);
        }
    }

    public function id() {
        return $this->id;
    }

    private function getPostArray(){
        if(!$this->title){
            return false;
        }
        $content = $this->content ?? self::templates_type;
        $status = $this->status ?? self::default_status;
        $title = $this->title;
        $name = $this->name;
        $type = self::post_type;
        $date = $this->get_post_date();
        $post_array = [
            'ID' => absint( $this->id ),
            'post_type' => $type,
            'post_name' => $name,
            'post_status' => $status,
            'post_title' => $title,
            'post_content' => $content,
            'post_date' => $this->get_post_date(),
        ];
        if ( $this->timestamp
            and $datetime = date_create( '@' . $this->timestamp ) ) {
            $datetime->setTimezone( wp_timezone() );
            $post_array['post_date'] = $datetime->format( 'Y-m-d H:i:s' );
        }
        return  $post_array;
    }
    public function save(){
        $post_array = $this->getPostArray();
        $post_id = wp_insert_post( $post_array );

        if($post_id){
            $this->id = $post_id;
            $this->updatePostMeta($post_id, self::input_to_check_meta_name,$this->input_to_check);
            $this->updatePostMeta($post_id, self::form_to_check_slug_meta_name,$this->form_to_check_slug);
        }
    }

    public function updatePost(){
        $post_array = $this->getPostArray();
        $post_id = wp_update_post( $post_array,false, false );

        if($post_id){
            $this->updatePostMeta($post_id, self::input_to_check_meta_name,$this->input_to_check);
            $this->updatePostMeta($post_id, self::form_to_check_slug_meta_name,$this->form_to_check_slug);
        }
    }
    private function get_post_date() {
        if ( empty( $this->id ) ) {
            return false;
        }

        $post = get_post( $this->id );

        if ( ! $post ) {
            return false;
        }

        return $post->post_date;
    }

    public function content_serialize($data){
        if( !is_serialized( $data ) ) {
            $data = maybe_serialize($data);
        }
        return $data;
    }

    public function content_unserialize($data){
        if( !is_serialized( $data ) ) {
            $data = maybe_unserialize($data);
        }
        return $data;
    }

    private  function updatePostMeta($id, $key, $value ){
        return update_post_meta($id, $key, $value);
    }

    private  function  getPostMeta($id, $key)
    {
        return get_post_meta($id, $key, true);
    }
    public function delete() {
        if ( empty( $this->id ) ) {
            return;
        }

        if ( $post = wp_delete_post( $this->id, true ) ) {
            $this->id = 0;
        }

        return (bool) $post;
    }

    public  static  function  delete_mail($id){
        global $wpdb;
        $table = $wpdb->posts;
        if(empty($id)){
            $id = self::$id;
        }
        $wpdb->delete($table, ["ID" => $id]);
        //TODO add to delete the child
    }
}