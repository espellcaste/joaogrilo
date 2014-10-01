<?php
/**
 * JoaoGrilo Settings API Wrapper Class
 *
 * @package JoaoGrilo
 * 
 * @version (1.0)
 * 
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'JoaoGrilo_Settings_API' ) ) :

    class JoaoGrilo_Settings_API {

        /**
        * Main JoaoGrilo_Settings_API Instance.
        *
        * Insures that only one instance of JoaoGrilo_Settings_API Class exists in memory at any
        * one time. Also prevents needing to define globals all over the place.
        *
        * @since JoaoGrilo (1.0)
        *
        */
        public static function instance() {

            // Store the instance locally to avoid private static replication
            static $instance = null;

            // Only run these methods if they haven't been run previously
            if ( null === $instance ) {
                
                $instance = new JoaoGrilo_Settings_API;
            }

            // Always return the instance
            return $instance;
        }

        /** Magic Methods *********************************************************/

        /**
         * A dummy constructor to JoaoGrilo_Settings_API Class
         *
         * @since JoaoGrilo (1.0)
         * 
         * @access public
         * 
         */
        public function __construct() { 
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) ); 
        }

        /**
         * Settings Sections
         *
         * @var array
         *
         * @access private
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        private $settings_sections = array();

        /**
         * Settings Fields
         *
         * @var array
         *
         * @access private
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        private $settings_fields = array();

        /**
         * Enqueue scripts and styles
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function admin_enqueue_scripts() {

            wp_enqueue_style( 'wp-color-picker' );

            wp_enqueue_media();
            wp_enqueue_script( 'wp-color-picker' );
            wp_enqueue_script( 'jquery' );
        }

        /**
         * Set settings sections
         *
         * @param array
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function set_sections( $sections ) {
            $this->settings_sections = $sections;

            return $this;
        }

        /**
         * Add a single section
         *
         * @param array   $section
         */
        public function add_section( $section ) {
            $this->settings_sections[] = $section;

            return $this;
        }

        /**
         * Set settings fields
         *
         * @param array   $fields settings fields array
         */
        public function set_fields( $fields ) {
            $this->settings_fields = $fields;

            return $this;
        }

        public function add_field( $section, $field ) {
            $defaults = array(
                'name' => '',
                'label' => '',
                'desc' => '',
                'type' => 'text'
            );

            $arg = wp_parse_args( $field, $defaults );
            $this->settings_fields[$section][] = $arg;

            return $this;
        }

        /**
         * Initialize and registers the settings sections and files to WordPress
         *
         * Usually this should be called at `admin_init` hook.
         *
         * This function gets the initiated settings sections and fields. Then
         * registers them to WordPress and ready for use.
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function admin_init() {

            //register settings sections
            foreach ( $this->settings_sections as $section ) {
                if ( false == get_option( $section['id'] ) ) {
                    // add_option( $section['id'] );

                    $defaults = array();

                    foreach ( $this->settings_fields[$section['id']] as $field ) {
                        $defaults[$field['name']] = isset( $field['default'] ) ? $field['default'] : '';
                    }

                    add_option( $section['id'], $defaults );
                }

                if ( isset($section['desc']) && ! empty($section['desc']) ) {
                    $section['desc'] = '<div class="inside">'. $section['desc'] . '</div>';
                    $callback = create_function('', 'echo "'. str_replace('"', '\"', $section['desc']) . '";');
                
                } else {

                    $callback = '__return_false';
                }

                add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
            }

            //register settings fields
            foreach ( $this->settings_fields as $section => $field ) {
                foreach ( $field as $option ) {

                    $type = isset( $option['type'] ) ? $option['type'] : 'text';

                    $args = array(
                        'id' => $option['name'],
                        'desc' => isset( $option['desc'] ) ? $option['desc'] : '',
                        'name' => $option['label'],
                        'section' => $section,
                        'size' => isset( $option['size'] ) ? $option['size'] : null,
                        'options' => isset( $option['options'] ) ? $option['options'] : '',
                        'std' => isset( $option['default'] ) ? $option['default'] : '',
                        'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
                    );

                    add_settings_field( $section . '[' . $option['name'] . ']', $option['label'], array( $this, 'callback_' . $type ), $section, $section, $args );
                }
            }

            // creates our settings in the options table
            foreach ( $this->settings_sections as $section ) {
                register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
            }
        }

        /**
         * Displays a text field for a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_text( $args ) {

            $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
            $size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

            $html = sprintf( '<input type="text" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
            $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

            echo $html;
        }

        /**
         * Displays a checkbox for a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_checkbox( $args ) {

            $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

            $html = sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
            $html .= sprintf( '<input type="checkbox" class="checkbox" id="jg-%1$s[%2$s]" name="%1$s[%2$s]" value="on"%4$s />', $args['section'], $args['id'], $value, checked( $value, 'on', false ) );
            $html .= sprintf( '<label for="-%1$s[%2$s]"> %3$s</label>', $args['section'], $args['id'], $args['desc'] );

            echo $html;
        }

        /**
         * Displays a multicheckbox a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_multicheck( $args ) {

            $value = $this->get_option( $args['id'], $args['section'], $args['std'] );

            $html = '';
            foreach ( $args['options'] as $key => $label ) {
                $checked = isset( $value[$key] ) ? $value[$key] : '0';
                $html .= sprintf( '<input type="checkbox" class="checkbox" id="jg-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
                $html .= sprintf( '<label for="jg-%1$s[%2$s][%4$s]"> %3$s</label><br>', $args['section'], $args['id'], $label, $key );
            }
            $html .= sprintf( '<span class="description"> %s</label>', $args['desc'] );

            echo $html;
        }

        /**
         * Displays a multicheckbox a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_radio( $args ) {

            $value = $this->get_option( $args['id'], $args['section'], $args['std'] );

            $html = '';
            foreach ( $args['options'] as $key => $label ) {
                $html .= sprintf( '<input type="radio" class="radio" id="jg-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
                $html .= sprintf( '<label for="jg-%1$s[%2$s][%4$s]"> %3$s</label><br>', $args['section'], $args['id'], $label, $key );
            }
            $html .= sprintf( '<span class="description"> %s</label>', $args['desc'] );

            echo $html;
        }

        /**
         * Displays a selectbox for a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_select( $args ) {

            $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
            $size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

            $html = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );
            foreach ( $args['options'] as $key => $label ) {
                $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
            }
            $html .= sprintf( '</select>' );
            $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

            echo $html;
        }

        /**
         * Displays a textarea for a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_textarea( $args ) {

            $value = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
            $size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

            $html = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]">%4$s</textarea>', $size, $args['section'], $args['id'], $value );
            $html .= sprintf( '<br><span class="description"> %s</span>', $args['desc'] );

            echo $html;
        }

        /**
         * Displays a textarea for a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_html( $args ) {
            echo $args['desc'];
        }

        /**
         * Displays a rich text textarea for a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_wysiwyg( $args ) {

            $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
            $size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : '500px';

            echo '<div style="width: ' . $size . ';">';

            wp_editor( $value, $args['section'] . '-' . $args['id'] . '', array( 'teeny' => true, 'textarea_name' => $args['section'] . '[' . $args['id'] . ']', 'textarea_rows' => 10 ) );

            echo '</div>';

            echo sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
        }

        /**
         * Displays a file upload field for a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_file( $args ) {

            $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
            $size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
            $id = $args['section']  . '[' . $args['id'] . ']';

            $html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
            $html .= '<input type="button" class="button wpsa-browse" value="'.__( 'Browse' ).'" />';

            $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

            echo $html;
        }

        /**
         * Displays a password field for a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_password( $args ) {

            $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
            $size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

            $html = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
            $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

            echo $html;
        }

        /**
         * Displays a color picker field for a settings field
         *
         * @param array   $args settings field args
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function callback_color( $args ) {

            $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
            $size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

            $html = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'] );
            $html .= sprintf( '<span class="description" style="display:block;"> %s</span>', $args['desc'] );

            echo $html;
        }

        /**
         * Sanitize callback for Settings API
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function sanitize_options( $options ) {
            foreach( $options as $option_slug => $option_value ) {
                $sanitize_callback = $this->get_sanitize_callback( $option_slug );

                // If callback is set, call it
                if ( $sanitize_callback ) {
                    $options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
                    continue;
                }
            }

            return $options;
        }

        /**
         * Get sanitization callback for given option slug
         *
         * @param string $slug option slug
         *
         * @return mixed string or bool false
         * 
         * @since JoaoGrilo (1.0)
         * 
         */
        public function get_sanitize_callback( $slug = '' ) {
            if ( empty( $slug ) ) {
                return false;
            }

            // Iterate over registered fields and see if we can find proper callback
            foreach( $this->settings_fields as $section => $options ) {
                foreach ( $options as $option ) {
                    if ( $option['name'] != $slug ) {
                        continue;
                    }

                    // Return the callback name
                    return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
                }
            }

            return false;
        }

        /**
         * Get the value of a settings field
         *
         * @param string  $option  Settings field name
         * @param string  $section The section name this field belongs to
         * @param string  $default Default text if it's not found
         * @return string
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function get_option( $option, $section, $default = '' ) {

            $options = get_option( $section );

            if ( isset( $options[$option] ) ) {
                return $options[$option];
            }

            return $default;
        }

        /**
         * Show navigations as tab
         *
         * Shows all the settings section labels as tab
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function show_navigation() {
            $html = '<h2 class="nav-tab-wrapper">';

            foreach ( $this->settings_sections as $tab ) {
                $html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
            }

            $html .= '</h2>';

            echo $html;
        }

        /**
         * Show the section settings forms
         *
         * This function displays every sections in a different form
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function show_forms() { ?>
            
            <div class="metabox-holder">
                <div class="postbox">

                    <?php foreach ( $this->settings_sections as $form ) { ?>
                        
                        <div id="<?php echo $form['id']; ?>" class="group inside">
                            
                            <form method="post" action="options.php">

                                <?php do_action( 'jg_form_top_' . $form['id'], $form ); ?>
                                
                                <?php settings_fields( $form['id'] ); ?>

                                <?php do_settings_sections( $form['id'] ); ?>

                                <?php do_action( 'jg_form_bottom_' . $form['id'], $form ); ?>

                                <div style="padding-left: 10px">
                                    <?php submit_button(); ?>
                                </div>
                            </form>

                        </div>

                    <?php } ?>
                </div>
            </div>

            <?php
            $this->script();
        }

        /**
         * Tabbable JavaScript codes & Initiate Color Picker
         *
         * This code uses localstorage for displaying active tabs
         *
         * @since JoaoGrilo (1.0)
         * 
         */
        public function script() {
            ?>
            <script>
                jQuery(document).ready(function($) {

                    //Initiate Color Picker
                    $('.wp-color-picker-field').wpColorPicker();

                    // Switches option sections
                    $('.group').hide();

                    var activetab = '';

                    if (typeof(localStorage) != 'undefined' ) {
                        activetab = localStorage.getItem("activetab");
                    }
                    
                    if (activetab != '' && $(activetab).length ) {
                        $(activetab).fadeIn();
                    } else {
                        $('.group:first').fadeIn();
                    }

                    $('.group .collapsed').each(function(){
                        $(this).find('input:checked').parent().parent().parent().nextAll().each(
                        function(){
                            if ($(this).hasClass('last')) {
                                $(this).removeClass('hidden');
                                return false;
                            }
                            $(this).filter('.hidden').removeClass('hidden');
                        });
                    });

                    if (activetab != '' && $(activetab + '-tab').length ) {
                        $(activetab + '-tab').addClass('nav-tab-active');
                    }
                    else {
                        $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
                    }
                    $('.nav-tab-wrapper a').click(function(evt) {
                        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                        $(this).addClass('nav-tab-active').blur();
                        var clicked_group = $(this).attr('href');
                        if (typeof(localStorage) != 'undefined' ) {
                            localStorage.setItem("activetab", $(this).attr('href'));
                        }
                        $('.group').hide();
                        $(clicked_group).fadeIn();
                        evt.preventDefault();
                    });

                    var file_frame = null;
                    $('.wpsa-browse').on('click', function (event) {
                        event.preventDefault();

                        var self = $(this);

                        // If the media frame already exists, reopen it.
                        if ( file_frame ) {
                            file_frame.open();
                            return false;
                        }

                        // Create the media frame.
                        file_frame = wp.media.frames.file_frame = wp.media({
                            title: self.data('uploader_title'),
                            button: {
                                text: self.data('uploader_button_text'),
                            },
                            multiple: false
                        });

                        file_frame.on('select', function () {
                            attachment = file_frame.state().get('selection').first().toJSON();

                            self.prev('.wpsa-url').val(attachment.url);
                        });

                        // Finally, open the modal
                        file_frame.open();
                    });
            });
            </script>

            <style type="text/css">
                /** WordPress 3.8 Fix **/
                .form-table th { padding: 20px 10px; }
                #wpbody-content .metabox-holder { padding-top: 5px; }
            </style>
            <?php
        }

    }

endif;