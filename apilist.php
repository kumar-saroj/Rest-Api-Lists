<?php
/////////////////// Register Api /////////////

add_action('rest_api_init', 'wp_rest_user_endpoints');
/**
 * Register a new user
 *
 * @param  WP_REST_Request $request Full details about the request.
 * @return array $args.
 **/
function wp_rest_user_endpoints($request) {
  /**
   * Handle Register User request.
   */
  register_rest_route('wp/v2', 'users/register', array(
    'methods' => 'POST',
    'callback' => 'wc_rest_user_endpoint_handler',
  ));
}
function wc_rest_user_endpoint_handler($request = null) {
  $response = array();
  $parameters = $request->get_json_params();
  $username = sanitize_text_field($parameters['username']);
  $first_name = sanitize_text_field($parameters['first_name']);
  $last_name = sanitize_text_field($parameters['last_name']);
  $email = sanitize_text_field($parameters['email']);
  $phone = sanitize_text_field($parameters['phone']);
  $dob = sanitize_text_field($parameters['dob']);
  $password = sanitize_text_field($parameters['password']);
  // $role = sanitize_text_field($parameters['role']);
  $error = new WP_Error();
  if (empty($username)) {
    $error->add(400, __("Username field 'username' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($first_name)) {
    $error->add(400, __("First Name field 'first_name' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($last_name)) {
    $error->add(400, __("Last Name field 'last_name' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($email)) {
    $error->add(401, __("Email field 'email' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($phone)) {
    $error->add(401, __("Phone field 'phone' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($dob)) {
    $error->add(401, __("Date Of Birth field 'dob' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($password)) {
    $error->add(404, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  // if (empty($role)) {
  //  $role = 'subscriber';
  // } else {
  //     if ($GLOBALS['wp_roles']->is_role($role)) {
  //      // Silence is gold
  //     } else {
  //    $error->add(405, __("Role field 'role' is not a valid. Check your User Roles from Dashboard.", 'wp_rest_user'), array('status' => 400));
  //    return $error;
  //     }
  // }
  $user_id = username_exists($username);
  if (!$user_id && email_exists($email) == false) {
    $user_id = wp_create_user($username, $password, $email);
    if (!is_wp_error($user_id)) {
      // Ger User Meta Data (Sensitive, Password included. DO NOT pass to front end.)
      $user = get_user_by('id', $user_id);
      // $user->set_role($role);
      $user->set_role('author');
      update_user_meta( $user_id, 'first_name', $first_name );
      update_user_meta( $user_id, 'last_name', $last_name );
      update_user_meta( $user_id, 'phone', $phone );
      update_user_meta( $user_id, 'dob', $dob );
      // WooCommerce specific code
      if (class_exists('WooCommerce')) {
        $user->set_role('customer');
      }
      // Ger User Data (Non-Sensitive, Pass to front end.)
      $response['code'] = 200;
      $response['message'] = __("User '" . $username . "' Registration was Successful", "wp-rest-user");
    } else {
      return $user_id;
    }
  } else {
    $error->add(406, __("Email already exists, please try 'Reset Password'", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  return new WP_REST_Response($response, 123);
}



////////////////// Book API ////////////////
////// UrL Endpoint : https://xyz.com/wp-json/books/list

add_action( 'rest_api_init', 'my_booklists' );

function my_booklists() {
    register_rest_route( 'books', 'list', array(
                    'methods' => 'GET',
                    'callback' => 'custom_phrase',
                )
            );
}

function custom_phrase() {
    $posts_list = get_posts( array( 'post_type' => 'book','numberposts'=>-1 ) );
    $post_data = array();

    foreach( $posts_list as $posts) {
        $post_id = $posts->ID;
        $post_author = $posts->post_author;
        $recent_author = get_user_by( 'ID', $post_author );
        $post_title = $posts->post_title;
        $post_content = $posts->post_content;
        $post_excerpt = wp_trim_words( $posts->post_content, 20, '...' );
        $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;

        /*$post_data[ $post_id ][ 'authorID' ] = $post_author;
        $post_data[ $post_id ][ 'bookID' ] = $post_id;
        $post_data[ $post_id ][ 'authorName' ] = $recent_author->display_name;;
        $post_data[ $post_id ][ 'title' ] = $post_title;
        $post_data[ $post_id ][ 'content' ] = $post_content;
        $post_data[ $post_id ][ 'excerpt' ] = $post_excerpt;
        $post_data[ $post_id ][ 'image' ] = $post_image[0];
        $post_data[ $post_id ][ 'audioURL' ] = get_post_meta($post_id,'bookaudio',true);
        $post_data[ $post_id ][ 'publication' ] = get_post_meta($post_id,'publication',true);*/
		
		$post_data[] = array(
                   'authorID' => $post_author,
                   'bookID' => $post_id,
			       'authorName' => $recent_author->display_name,
			       'title' => $post_title,
			       'content' => $post_content,
			       'excerpt' => $post_excerpt,
			       'image' => $post_image[0],
			       'audioURL' => get_post_meta($post_id,'bookaudio',true),
			       'publication' => get_post_meta($post_id,'publication',true),
             'credits' => get_post_meta($post_id,'credits',true)
			
         );

    }

    wp_reset_postdata();
    return rest_ensure_response( $post_data );
}

///////////// Get Book Through ID ///////////////////////

add_action( 'rest_api_init', 'my_booklistsbyid' );

function my_booklistsbyid() {
    register_rest_route( 'book', 'lists/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => 'custom_phrasebyid',
                )
            );
}

function custom_phrasebyid($data) {

    $id = $data['id'];
    $posts_list = get_posts( array( 'post_type' => 'book','p' => $id ) );
    $post_data = array();

    foreach( $posts_list as $posts) {
        $post_id = $posts->ID;
        $post_author = $posts->post_author;
        $recent_author = get_user_by( 'ID', $post_author );
        $post_title = $posts->post_title;
        $post_content = $posts->post_content;
        $post_excerpt = wp_trim_words( $posts->post_content, 20, '...' );
        $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;

        setPostViews($post_id);

        /*$post_data[ $post_id ][ 'authorID' ] = $post_author;
        $post_data[ $post_id ][ 'bookID' ] = $post_id;
        $post_data[ $post_id ][ 'authorName' ] = $recent_author->display_name;;
        $post_data[ $post_id ][ 'title' ] = $post_title;
        $post_data[ $post_id ][ 'content' ] = $post_content;
        $post_data[ $post_id ][ 'excerpt' ] = $post_excerpt;
        $post_data[ $post_id ][ 'image' ] = $post_image[0];
        $post_data[ $post_id ][ 'audioURL' ] = get_post_meta($post_id,'bookaudio',true);
        $post_data[ $post_id ][ 'publication' ] = get_post_meta($post_id,'publication',true);*/
		
		$post_data[] = array(
             'authorID' => $post_author,
             'bookID' => $post_id,
			       'authorName' => $recent_author->display_name,
			       'title' => $post_title,
			       'content' => $post_content,
			       'excerpt' => $post_excerpt,
			       'image' => $post_image[0],
			       'audioURL' => get_post_meta($post_id,'bookaudio',true),
			       'publication' => get_post_meta($post_id,'publication',true),
             'credits' => get_post_meta($post_id,'credits',true)
			
         );

    }

    wp_reset_postdata();
    return rest_ensure_response( $post_data );
}



///////////// Get Book Through Author ///////////////////////

add_action( 'rest_api_init', 'my_booklistsbyauthorid' );

function my_booklistsbyauthorid() {
    register_rest_route( 'book', 'author/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => 'custom_phrasebyauthorid',
                )
            );
}

function custom_phrasebyauthorid($data) {

    
    $id = $data['id'];
    $posts_list = get_posts( array( 'post_type' => 'book','author' => $id,'numberposts'=>-1 ) );
    $post_data = array();

    foreach( $posts_list as $posts) {
        $post_id = $posts->ID;
        $post_author = $posts->post_author;
        $recent_author = get_user_by( 'ID', $post_author );
        $post_title = $posts->post_title;
        $post_content = $posts->post_content;
        $post_excerpt = wp_trim_words( $posts->post_content, 20, '...' );
        $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;

        /*$post_data[ $post_id ][ 'authorID' ] = $post_author;
        $post_data[ $post_id ][ 'bookID' ] = $post_id;
        $post_data[ $post_id ][ 'authorName' ] = $recent_author->display_name;;
        $post_data[ $post_id ][ 'title' ] = $post_title;
        $post_data[ $post_id ][ 'content' ] = $post_content;
        $post_data[ $post_id ][ 'excerpt' ] = $post_excerpt;
        $post_data[ $post_id ][ 'image' ] = $post_image[0];
        $post_data[ $post_id ][ 'audioURL' ] = get_post_meta($post_id,'bookaudio',true);
        $post_data[ $post_id ][ 'publication' ] = get_post_meta($post_id,'publication',true);*/
		
		$post_data[] = array(
             'authorID' => $post_author,
             'bookID' => $post_id,
			       'authorName' => $recent_author->display_name,
			       'title' => $post_title,
			       'content' => $post_content,
			       'excerpt' => $post_excerpt,
			       'image' => $post_image[0],
			       'audioURL' => get_post_meta($post_id,'bookaudio',true),
			       'publication' => get_post_meta($post_id,'publication',true),
             'credits' => get_post_meta($post_id,'credits',true)
			
         );

    }

    wp_reset_postdata();
    return rest_ensure_response( $post_data );
}




///////////// Get Book Through Category ///////////////////////

add_action( 'rest_api_init', 'my_booklistsbycatid' );

function my_booklistsbycatid() {
    register_rest_route( 'book', 'category/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => 'custom_phrasebycategoryid',
                )
            );
}

function custom_phrasebycategoryid($data) {

    
    $id = $data['id'];

    $args = array(
        'post_type' => 'book',
        'numberposts'=>-1,
        'tax_query' => array(
            array(
                'taxonomy' => 'book-category',
                'field'    => 'term_id',
                'terms'    => $id
            )
        )
    );
    $posts_list = get_posts( $args );

    
    $post_data = array();

    foreach( $posts_list as $posts) {
        $post_id = $posts->ID;
        $post_author = $posts->post_author;
        $recent_author = get_user_by( 'ID', $post_author );
        $post_title = $posts->post_title;
        $post_content = $posts->post_content;
        $post_excerpt = wp_trim_words( $posts->post_content, 20, '...' );
        $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;

        /*$post_data[ $post_id ][ 'authorID' ] = $post_author;
        $post_data[ $post_id ][ 'bookID' ] = $post_id;
        $post_data[ $post_id ][ 'authorName' ] = $recent_author->display_name;;
        $post_data[ $post_id ][ 'title' ] = $post_title;
        $post_data[ $post_id ][ 'content' ] = $post_content;
        $post_data[ $post_id ][ 'excerpt' ] = $post_excerpt;
        $post_data[ $post_id ][ 'image' ] = $post_image[0];
        $post_data[ $post_id ][ 'audioURL' ] = get_post_meta($post_id,'bookaudio',true);
        $post_data[ $post_id ][ 'publication' ] = get_post_meta($post_id,'publication',true);*/
		
		$post_data[] = array(
                   'authorID' => $post_author,
                   'bookID' => $post_id,
			       'authorName' => $recent_author->display_name,
			       'title' => $post_title,
			       'content' => $post_content,
			       'excerpt' => $post_excerpt,
			       'image' => $post_image[0],
			       'audioURL' => get_post_meta($post_id,'bookaudio',true),
			       'publication' => get_post_meta($post_id,'publication',true),
             'credits' => get_post_meta($post_id,'credits',true)
			
         );

    }

    wp_reset_postdata();
    return rest_ensure_response( $post_data );
}

/////////////////////// Get categories /////////


add_action( 'rest_api_init', 'gettaxnomie' );

function gettaxnomie() {
    register_rest_route( 'recite', 'category', array(
                    'methods' => 'GET',
                    'callback' => 'getcategories',
     )
    );
}

function getcategories()
{
    $terms = get_terms( array(
        'taxonomy' => 'book-category',
        'hide_empty' => false,
    ) );
    $post_data = array();


    foreach($terms as $term)
    {
       /*$post_data[$term->term_id]['termID'] =  $term->term_id;
       $post_data[$term->term_id]['name'] =  $term->name;
       $post_data[$term->term_id]['description'] =  $term->description;
       $post_data[$term->term_id]['count'] =  $term->count;*/
		
		$post_data[] = array(
		  'termID' => $term->term_id,
		  'name' => $term->name,
		  'description' => $term->description,
		  'count' => $term->count	
		);
		
    }
    return rest_ensure_response( $post_data );
}

////////////////// Get Authors ////////////////////

add_action( 'rest_api_init', 'getauthors' );

function getauthors() {
    register_rest_route( 'recite', 'authors', array(
        'methods' => 'GET',
        'callback' => 'getauthorslist',
     )
    );
}

function getauthorslist()
{
    $blogusers = get_users( array( 'role__in' => array( 'author' ) ) );
    $post_data = array();
    foreach ( $blogusers as $user ) {
        /*$post_data[$user->ID]['userID'] =  $user->ID;
        $post_data[$user->ID]['firstname'] =  $user->first_name;
        $post_data[$user->ID]['lastname'] =  $user->last_name;
        $post_data[$user->ID]['displayname'] =  $user->display_name;
        $post_data[$user->ID]['bio'] =  $user->description;
        $post_data[$user->ID]['email'] =  $user->user_email;
        $post_data[$user->ID]['phone'] =  get_user_meta($user->ID,'phone',true);
        $post_data[$user->ID]['dob'] =  get_user_meta($user->ID,'dob',true);
        $post_data[$user->ID]['image'] =  get_avatar_url( $user->ID,['size' => '300']);*/
		
		$post_data[] = array(
		  'userID' => $user->ID,
		  'firstname' => $user->first_name,
		  'lastname' => $user->last_name,
		  'displayname' => $user->display_name,
		  'bio' => $user->description,
		  'email' => $user->user_email,
		  'phone' => get_user_meta($user->ID,'phone',true),
		  'dob' => get_user_meta($user->ID,'dob',true),
		  'image' => get_avatar_url( $user->ID,['size' => '300'])
		);
    }
    return rest_ensure_response( $post_data );
}

/////////////// Adding Userwise Song /////////////////
add_action('rest_api_init', 'wp_add_lastsong');
function wp_add_lastsong() {
    register_rest_route( 'recite', 'lastsong', array(
        'methods' => 'POST',
        'callback' => 'savelastsong',
    )
    );
  }
  function savelastsong($request )
  {
     $userid = (int)$request['userId'];
     $lastsongid = (int)$request['lastSongId'];
     $playedupto = $request['playedUpto'];
     if(empty($lastsongid))
     {
      $return = array(
        'message' => __( 'Last song ID cannot be blank!', 'textdomain' )
       );
   
         wp_send_json_error( $return );
     }
     elseif(empty($playedupto))
     {
      $return = array(
        'message' => __( 'Upto played cannot be blank!', 'textdomain' )
       );
   
         wp_send_json_error( $return );
     }
     else
     {
     update_user_meta( $userid, 'lastSongId', $lastsongid );
     update_user_meta( $userid, 'playedUpto', $playedupto );

     $return = array(
			'success'      => 1
		);
		wp_send_json_success( $return );
  }
  }


add_action( 'rest_api_init', 'getsonginfo' );

function getsonginfo() {
    register_rest_route( 'recite', 'songinfo/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => 'usersonginfo',
                )
      );    
}
function usersonginfo($request)
{
  $userid = $request['id'];
  $post_data = array();

  $post_data[] = array(
    'lastSongId' => get_user_meta($userid,'lastSongId',true),
    'playedUpto' => get_user_meta($userid,'playedUpto',true)
 );
 return rest_ensure_response( $post_data );
}

//////////// Get Book By Searchquery ///////////

add_action( 'rest_api_init', 'addinggenericsearch' );

function addinggenericsearch() {
    register_rest_route( 'recite', 'searchresult/(?P<username>[a-zA-Z0-9-]+)', array(
                    'methods' => 'GET',
                    'callback' => 'getsearchquery',
                )
      );    
}
function getsearchquery($request)
{
  global $wpdb;
  $p = $request['username'];
  //echo $p; die();
  
  $result = $wpdb->get_results("select ID from wp_users where display_name like '%$p%'");
  
  //print_r($result); die();
  $userID =  (int)$result[0]->ID;
 
  
  //$user = get_user_by('login',$p);
  $numlength = strlen((int)$p);
  //$userid = (int)$user->ID;
  $post_data = array();

  if($userID)
  {

    $args=array(
      'post_type' => 'book',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'author' => $userID
       );
     
     $current_user_posts = get_posts( $args );
     //$total = count($current_user_posts);
     foreach( $current_user_posts as $posts) {
      $post_id = $posts->ID;
      $post_author = $posts->post_author;
      $recent_author = get_user_by( 'ID', $post_author );
      $post_title = $posts->post_title;
      $post_content = $posts->post_content;
      $post_excerpt = wp_trim_words( $posts->post_content, 20, '...' );
      $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;
      $post_data[] = array(
        'authorID' => $post_author,
        'bookID' => $post_id,
        'authorName' => $recent_author->display_name,
        'title' => $post_title,
        'content' => $post_content,
        'excerpt' => $post_excerpt,
        'image' => $post_image[0],
        'audioURL' => get_post_meta($post_id,'bookaudio',true),
        'publication' => get_post_meta($post_id,'publication',true),
        'credits' => get_post_meta($post_id,'credits',true)
      );
    }
  }
  elseif($numlength == 4)
  {
    $args=array(
      'post_type' => 'book',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'meta_query'    =>  array(
        array(
            'key'     => 'publication', // assumed your meta_key is 'car_model'
            'value'   => $p,
            'compare' => 'LIKE', // finds models that matches 'model' from the select field
        ),
       )
       );
     
     $current_user_posts = get_posts( $args );
     //$total = count($current_user_posts);
     foreach( $current_user_posts as $posts) {
      $post_id = $posts->ID;
      $post_author = $posts->post_author;
      $recent_author = get_user_by( 'ID', $post_author );
      $post_title = $posts->post_title;
      $post_content = $posts->post_content;
      $post_excerpt = wp_trim_words( $posts->post_content, 20, '...' );
      $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;
      $post_data[] = array(
        'authorID' => $post_author,
        'bookID' => $post_id,
        'authorName' => $recent_author->display_name,
        'title' => $post_title,
        'content' => $post_content,
        'excerpt' => $post_excerpt,
        'image' => $post_image[0],
        'audioURL' => get_post_meta($post_id,'bookaudio',true),
        'publication' => get_post_meta($post_id,'publication',true),
        'credits' => get_post_meta($post_id,'credits',true)
      );
    }
  }
 
  else{
    $args=array(
      'post_type' => 'book',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      's' => $p
       );
     
     $current_user_posts = get_posts( $args );
     //$total = count($current_user_posts);
     foreach( $current_user_posts as $posts) {
      $post_id = $posts->ID;
      $post_author = $posts->post_author;
      $recent_author = get_user_by( 'ID', $post_author );
      $post_title = $posts->post_title;
      $post_content = $posts->post_content;
      $post_excerpt = wp_trim_words( $posts->post_content, 20, '...' );
      $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;
      $post_data[] = array(
        'authorID' => $post_author,
        'bookID' => $post_id,
        'authorName' => $recent_author->display_name,
        'title' => $post_title,
        'content' => $post_content,
        'excerpt' => $post_excerpt,
        'image' => $post_image[0],
        'audioURL' => get_post_meta($post_id,'bookaudio',true),
        'publication' => get_post_meta($post_id,'publication',true),
        'credits' => get_post_meta($post_id,'credits',true)
      );
    }
  }

  
 return rest_ensure_response( $post_data );
}


/////////////// Adding Library //////////////////


add_action('rest_api_init', 'wp_add_librarydetails');
function wp_add_librarydetails() {
    register_rest_route( 'recite', 'library', array(
        'methods' => 'POST',
        'callback' => 'savelibrary',
    )
    );
  }
  function savelibrary($request )
  {
     $userid = (int)$request['userId'];
     $postid = (int)$request['bookid'];
     if(empty($userid))
     {
      $return = array(
        'message' => __( 'User ID cannot be blank!', 'textdomain' )
       );
   
         wp_send_json_error( $return );
     }
     elseif(empty($postid))
     {
      $return = array(
        'message' => __( 'Book ID cannot be blank!', 'textdomain' )
       );
   
         wp_send_json_error( $return );
     }
     else
     {
     
      global $wpdb;		
      $table_name = $wpdb->prefix.'usermeta';
        
        $myrows = $wpdb->get_results("SELECT meta_value FROM $table_name WHERE meta_key = 'postID' and meta_value = $postid and `user_id` = $userid");
       
        $num = $wpdb->num_rows;
    
        if($num > 0)
        {
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET meta_value='$postid' WHERE `user_id`=$userid AND meta_value=$postid AND meta_key='postID'"));
        }
        else{
        
            $inse=$wpdb->insert(
          $table_name,
          array(
            'user_id' =>$userid ,
            'meta_key' =>'postID' ,
            'meta_value' => $postid
          )
        );
        $wpdb->query($inse);
        }

     $return = array(
			'success'      => 1
		);
		wp_send_json_success( $return );
    }
  }

  ///////////// Delete Library ////////////

  add_action('rest_api_init', 'wp_delete_librarydetails');
function wp_delete_librarydetails() {
    register_rest_route( 'recite', 'libraryDelete', array(
        'methods' => 'POST',
        'callback' => 'deletelibrary',
    )
    );
  }
  function deletelibrary($request )
  {
     $userid = (int)$request['userId'];
     $postid = (int)$request['bookid'];

     if(empty($userid))
     {
      $return = array(
        'message' => __( 'User ID cannot be blank!', 'textdomain' )
       );
   
         wp_send_json_error( $return );
     }
     elseif(empty($postid))
     {
      $return = array(
        'message' => __( 'Book ID cannot be blank!', 'textdomain' )
       );   
         wp_send_json_error( $return );
     }
     else{
      delete_user_meta($userid,'postID',$postid);
      $return = array(
        'success'      => 1
      );
      wp_send_json_success( $return );
     }

  }

  ///////////////////// Adding Post wise comment ////////////////

  add_action('rest_api_init', 'wp_adding_commentsystem');
  function wp_adding_commentsystem() {
      register_rest_route( 'recite', 'comment', array(
          'methods' => 'POST',
          'callback' => 'addcommenting',
      )
      );
    }
    function addcommenting($request )
    {  
        $postid = (int)$request['bookid'];
        $content = $request['comment'];
        $userid = (int)$request['userid'];
        $username = $request['authorname'];
        $email = $request['email'];
        $rating = (int)$request['star'];
        $current_user = wp_get_current_user();
        $time = current_time('mysql');
        $data = array(
          'comment_post_ID' => $postid,
          'comment_author' => $username,
          'comment_author_email' => $email,
          'comment_content' => $content,
          'user_id' => $userid,
          'comment_date' => $time,
          'comment_approved' => 1,
          'comment_type' => 'custom-comment-class'
      );
      $comment_id = wp_insert_comment($data);
      add_comment_meta( $comment_id, 'rating', $rating );
      $return = array(
        'success'      => 1
      );
      wp_send_json_success( $return );
      }
      

    //////////////// Get user wise library ////////////////

    add_action('rest_api_init', 'wpget_user_library');
  function wpget_user_library() {
      register_rest_route( 'recite', 'getuserwiseLibrary/(?P<id>\d+)', array(
          'methods' => 'GET',
          'callback' => 'get_userwise_library',
      )
      );
    }
    function get_userwise_library($request )
    {  

      $userid = (int)$request['id'];
      
        global $wpdb;		
        $table_name = $wpdb->prefix.'usermeta';
        $myrows = $wpdb->get_results("SELECT meta_value FROM $table_name WHERE meta_key = 'postID' and `user_id` = $userid");
        $post_data = array();
        foreach($myrows as $myrow)
        {
          $post_id = $myrow->meta_value;
          $author_id = get_post_field( 'post_author', $post_id );
          $author_name = get_the_author_meta( 'display_name', $author_id );
          $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;
          $post_data[] = array(
            'authorID' => $author_id,
            'bookID' => $post_id,
            'authorName' => $author_name,
            'title' => get_the_title($post_id),            
            'image' => $post_image[0],
            'audioUrl' => get_post_meta($post_id,'bookaudio',true)
          );
       
      }
      return rest_ensure_response( $post_data );

    }

    ///////////// edit profile api //////////


    add_action('rest_api_init', 'wp_rest_user_editprofile');

    function wp_rest_user_editprofile()
    {
       register_rest_route( 'wp/v2', 'User/Editprofile', array(
        'methods' => 'POST',
        'callback' => 'wpupdateprofile',
        )
        );
    }

    function wpupdateprofile($request)
    {
      $userid = (int)$request['userid'];
      $first_name = $request['firstName'];
      $last_name = $request['lastName'];
      $phone = $request['phone'];
      $dob = $request['dob'];

      update_user_meta( $userid, 'first_name', $first_name );
      update_user_meta( $userid, 'last_name', $last_name );
      update_user_meta( $userid, 'phone', $phone );
      update_user_meta( $userid, 'dob', $dob );

      $return = array(
        'success'      => 1
      );
      wp_send_json_success( $return );
    }


    ///////////////////// Change Password /////////////////

    add_action('rest_api_init', 'wp_rest_user_changepassword');

    function wp_rest_user_changepassword()
    {
       register_rest_route( 'wp/v2', 'User/Changepassword', array(
        'methods' => 'POST',
        'callback' => 'changepasswordforuser',
        )
        );
    }

    function changepasswordforuser($request)
    {
      $userid = (int)$request['userid'];
      $user = get_user_by( 'id', $userid );
      $password = $request['password'];
      $newpassword = $request['newpassword'];

      if(empty($userid)){
        $json = array('code'=>'0','msg'=>'Please enter user id');
        echo json_encode($json);
        exit;    
      }
      elseif(empty($password)){
        $json = array('code'=>'0','msg'=>'Please enter old password');
        echo json_encode($json);
        exit;    
      }
      elseif(empty($newpassword)){
        $json = array('code'=>'0','msg'=>'Please enter new password');
        echo json_encode($json);
        exit;    
      }
      else{
        $hash = $user->data->user_pass;
        $code = 500; $status = false;
        if (wp_check_password( $password, $hash ) ){
          
          $code = 200; $status = true;
          wp_set_password($newpassword , $userid);
          $return = array(
            'success'      => 1
          );
          wp_send_json_success( $return );
        }else{
          $json = array('code'=>'0','msg'=>'Current password does not match.');
          echo json_encode($json);
          exit;    
        }
      }
    }


    ////////////// Add media ////////////
    add_action('rest_api_init', 'wpaddmediauser');

    function wpaddmediauser()
    {
       register_rest_route( 'recite', 'addMedia', array(
        'methods' => 'POST',
        'callback' => 'changemediauser',
        )
        );
    }

    function changemediauser($request)
    {
      
      $json = array('code'=>'0','msg'=>$_SERVER['PHP_AUTH_USER']);
          echo json_encode($json);
          exit; 
    }



    //////////////// Most Commented Book /////////////////////

add_action( 'rest_api_init', 'mostcommentedbook' );

function mostcommentedbook() {
    register_rest_route( 'books', 'top', array(
                    'methods' => 'GET',
                    'callback' => 'custom_mostcommented',
                )
            );
}

function custom_mostcommented() {

    
    
    $posts_list = get_posts( array( 'post_type' => 'book','orderby' => 'comment_count','numberposts'=>10 ) );
    $post_data = array();

    foreach( $posts_list as $posts) {
        $post_id = $posts->ID;
        $post_author = $posts->post_author;
        $recent_author = get_user_by( 'ID', $post_author );
        $post_title = $posts->post_title;
        $post_content = $posts->post_content;
        $post_excerpt = wp_trim_words( $posts->post_content, 20, '...' );
        $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;
        $postcomment = get_comments_number($posts->ID);


        if($postcomment > 0) {

		
		$post_data[] = array(
             'authorID' => $post_author,
             'bookID' => $post_id,
			       'authorName' => $recent_author->display_name,
			       'title' => $post_title,
			       'content' => $post_content,
			       'excerpt' => $post_excerpt,
			       'image' => $post_image[0],
			       'audioURL' => get_post_meta($post_id,'bookaudio',true),
			       'publication' => get_post_meta($post_id,'publication',true),
             'credits' => get_post_meta($post_id,'credits',true),
             'comments' => $postcomment
			
         );
        }

    }

    wp_reset_postdata();
    return rest_ensure_response( $post_data );
}
////////////////// Lost Password ////////////////////
add_action( 'rest_api_init', 'recite_lostpassword' );

function recite_lostpassword() {
    register_rest_route( 'recite', 'lostpassword', array(
                    'methods' => 'POST',
                    'callback' => 'sendlost_passwordlink',
                )
            );
}

function sendlost_passwordlink($request) {
  $login = $request['email']; 
  $userdata = get_user_by( 'email', $login); 

  if ( empty( $login ) ) {
    $json = array( 'code' => '0', 'msg' => 'Please enter email address' );
    echo json_encode( $json );
    exit;     
  }
  elseif(empty( $userdata ))
  {
    $json = array( 'code' => '101', 'msg' => 'User not found' );
    echo json_encode( $json );
    exit;
  }
  else{

    $userData = get_userdata($userdata->ID);             

    $user_login = $userData->user_login;
    $user_email = $userData->user_email;
    $key = get_password_reset_key( $userData );
    
    $message = __('Someone requested that the password be reset for the following account:') . "<br>";
    $message .= network_home_url( '/' ) . "<br>";
    $message .= sprintf(__('Username: %s'), $user_login) . "<br>";
    $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "<br>";
    $message .= __('To reset your password, visit the following address:') . "<br>";
    $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');


    $subject = __("Your account on ".get_bloginfo( 'name'));
   $headers = array();

   add_filter( 'wp_mail_content_type', function( $content_type ) {return 'text/html';});
   $headers[] = 'From: Recite World <support@recite.world>'."\r\n";

    wp_mail( $login, $subject, $message, $headers);

    remove_filter( 'wp_mail_content_type', 'set_html_content_type' );

    $json = array( 'code' => '200', 'msg' => 'Password reset link has been sent to your registered email' );
    echo json_encode( $json );
    exit;
  }
}



///////////////// Most Viewed Book //////////////////



add_action( 'rest_api_init', 'mostviewdbooks' );

function mostviewdbooks() {
    register_rest_route( 'book', 'viewed', array(
                    'methods' => 'GET',
                    'callback' => 'custombookviewed',
                )
            );
}

function custombookviewed() {

    
    $posts_list = get_posts( array( 'post_type' => 'book','posts_per_page'=>10,'meta_key' => 'post_views_count','orderby' => 'meta_value_num') );
    $post_data = array();

    foreach( $posts_list as $posts) {
        $post_id = $posts->ID;
        $post_author = $posts->post_author;
        $recent_author = get_user_by( 'ID', $post_author );
        $post_title = $posts->post_title;
        $post_content = $posts->post_content;
        $post_excerpt = wp_trim_words( $posts->post_content, 20, '...' );
        $post_image = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full') ;

        
		$post_data[] = array(
             'authorID' => $post_author,
             'bookID' => $post_id,
			       'authorName' => $recent_author->display_name,
			       'title' => $post_title,
			       'content' => $post_content,
			       'excerpt' => $post_excerpt,
			       'image' => $post_image[0],
			       'audioURL' => get_post_meta($post_id,'bookaudio',true),
			       'publication' => get_post_meta($post_id,'publication',true),
             'credits' => get_post_meta($post_id,'credits',true),
             'viewed' => get_post_meta($post_id,'post_views_count',true)
			
         );

    }

    wp_reset_postdata();
    return rest_ensure_response( $post_data );
}
?>
