<?php
namespace LvdWcMc\PluginModules {

    use LvdWcMc\Plugin;

    class WebPageAssetsExtensionPointsProviderModule extends PluginModule {
        public function __construct(Plugin $plugin) {
            parent::__construct($plugin);
        }

        public function load() {
            $this->_registerWebPageAssetsExtensionPoints();
        }

        private function _registerWebPageAssetsExtensionPoints() {
            add_action('wp_enqueue_scripts', 
                array($this, 'onFrontendEnqueueStyles'), 10000);
            add_action('wp_enqueue_scripts', 
                array($this, 'onFrontendEnqueueScripts'), 10000);
            add_action('admin_enqueue_scripts', 
                array($this, 'onAdminEnqueueStyles'), 10000);
            add_action('admin_enqueue_scripts', 
                array($this, 'onAdminEnqueueScripts'), 10000);
        }

        public function onFrontendEnqueueStyles() {
            /**
             * Triggered after all the core-plug-in frontend styles 
             *  have been enqueued.
             * 
             * @hook lvdwcmc_frontend_enqueue_styles
             * 
             * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
             */
            do_action('lvdwcmc_frontend_enqueue_styles', 
                $this->_mediaIncludes);
        }

        public function onFrontendEnqueueScripts() {
            /**
             * Triggered after all the core-plug-in frontend scripts 
             *  have been enqueued.
             * 
             * @hook lvdwcmc_frontend_enqueue_scripts
             * 
             * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
             */
            do_action('lvdwcmc_frontend_enqueue_scripts', 
                $this->_mediaIncludes);
        }

        public function onAdminEnqueueStyles() {
            /**
             * Triggered after all the core-plug-in admin styles 
             *  have been enqueued.
             * 
             * @hook lvdwcmc_admin_enqueue_styles
             * 
             * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
             */
            do_action('lvdwcmc_admin_enqueue_styles', 
                $this->_mediaIncludes);
        }

        public function onAdminEnqueueScripts() {
            /**
             * Triggered after all the core-plug-in admin scripts 
             *  have been enqueued.
             * 
             * @hook lvdwcmc_admin_enqueue_scripts
             * 
             * @param \LvdWcMc\MediaIncludes $mediaIncludes Reference to the media includes manager
             */
            do_action('lvdwcmc_admin_enqueue_scripts', 
                $this->_mediaIncludes);
        }
    }
}