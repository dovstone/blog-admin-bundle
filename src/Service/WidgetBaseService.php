<?php
/**
 * Created by PhpStorm.
 * User: stOne
 * Date: 22/02/2018
 * Time: 16:45
 */

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class WidgetBaseService extends AbstractController
{
    protected $please;
    //
    private $reqS;
    private $formBuilderService;
    private $pageService;
    private $articleService;
    private $dirService;
    private $envService;
    private $navService;
    private $urlService;
    //
    private $swaggPickImage;

    private $__Repositories;

    public function __construct(PleaseService $please, $__Repositories = null)
    {
        $this->please = $please;
        //
        $this->__Repositories = (object) $__Repositories;

        $this->formBuilderService = $this->please->getBundleService('form_builder');
        $this->pageService = $this->please->getBundleService('page');
        $this->articleService = $this->please->getBundleService('article');
        $this->dirService = $this->please->getBundleService('dir');
        $this->envService = $this->please->getBundleService('env');
        $this->navService = $this->please->getBundleService('nav');
        $this->urlService = $this->please->getBundleService('url');
        //
        $APP_HOST = trim($_SERVER['APP_HOST'], '/');
        $APP_PREFIX = trim($_SERVER['APP_PREFIX'], '/');
        $BASE_URL = $APP_HOST . '/' . $APP_PREFIX;
        $CDN_HOST = trim($_SERVER['CDN_HOST'], '/');
        $this->swaggPickImage = $CDN_HOST . '/swagg/img/choisissez-votre-image.png';

        // widget name
        $path = explode('\\', get_class($this));
        $this->_name = array_pop($path);

    }

    private $_name;
    private $_title;
    private $_titleText;
    private $_icon;
    
    private $_hasController = false;

    private $_which_tab;

    private $_group_title;

    private $_stylesheet = '';
    private $_javascript = '';

    private $_css_rules = '';

    private $_content_tab_controls_group = [];
    private $_style_tab_controls_built = [];
    private $_advanced_tab_controls_built = [];

    private $_content_tab_controls_group_built = '';
    private $_style_tab_controls_group_built = '';
    private $_advanced_tab_controls_group_built = '';
    private $_bindjs_script_data = '';
    private $_bindjs_script_callback = '';

    private $_content_tab_controls_context = [];
    private $_style_tab_controls_context = [];
    private $_advanced_tab_controls_context = [];

    private $_conf_selectors = [];

    private $_element_id = '';
    private $_element_class = '';
    private $_element_attr = [];

    private $_container_id = '';
    private $_container_class = '';
    private $_container_attr = [];

    private $_widget_type;

    private $_ctrlerExecWatcher;
    private $__widgetBuiltBag = [];

    public function _ctrlerExecWatcher( $runController )
    {
        $this->_ctrlerExecWatcher = $runController;
        //
        return $this;
    }

    public function name($name)
    {
        $this->_name = $name;
        //
        return $this;
    }

    public function title($title)
    {
        if( is_null($this->_ctrlerExecWatcher) ){

            $this->_titleText = $title;
            $this->_title = '<div><span data-title="' . $title . '">' . $title . '</span></div>';
        }

        //
        return $this;
    }

    public function icon($icon)
    {
        if( is_null($this->_ctrlerExecWatcher) ){

            $this->_icon = '<i class="fa fa-' . $icon . '" data-icon="' . $icon . '"></i>';
        }

        //
        return $this;
    }

    public function hasController()
    {
        $this->_hasController = true;

        //
        return $this;
    }

    public function contentTab(callable $callback)
    {
        if( is_null($this->_ctrlerExecWatcher) ){

            $this->_which_tab = 'content';

            $callback($this);

        }

        //
        return $this;
    }

    public function styleTab(callable $callback)
    {
        if( is_null($this->_ctrlerExecWatcher) ){

            $this->_which_tab = 'style';

            $callback($this);
        }

        //
        return $this;
    }

    public function advancedTab(callable $callback)
    {
        if( is_null($this->_ctrlerExecWatcher) ){

            $this->_which_tab = 'advanced';

            $callback($this);
        }

        //
        return $this;
    }

    public function controlGroup($group_title, callable $callback)
    {
        $this->_group_title = $group_title;

        $callback($this);

        //
        return $this;
    }

    public function control($type, $name, $context = [], $cls = null)
    {
        if (method_exists($this, $type)) {

            $this->_widget_type = $type;

            $this->$type($name, $context, $cls);
        } else {
            $error = "<pre>";
            $error .= "<span style='color:red'>Error : Control <b><em>$type</em></b> is unknown</span><br>";
            $error .= "type : <b><em>$type</em></b> <br>";
            $error .= "name : <b><em>$name</em></b> <br>";
            $error .= "tab : <b><em>$this->_which_tab</em></b> <br>";
            $error .= "</pre>";
            die($error);
        }

        //
        return $this;
    }

    public function stylesheet(callable $stylesheet)
    {

        $this->_stylesheet = $stylesheet();

        //
        return $this;
    }

    public function javascript(callable $javascript)
    {
        $this->_javascript = $javascript();

        //
        return $this;
    }

    /*
    START WIDGETS
     */

    public function textarea($name, $context)
    {
        $context = (object) array_merge([
            'label' => 'Texte',
            'placeholder' => 'Saisissez votre texte',
            'default' => "Je suis un élément de titrage",
            'rows' => 4,
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="swagg-label-title">' . $context->label . '</span>
                        ' . $this->formBuilderService->formControlTextarea($name . '.default', 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm" rows="' . $context->rows . '" placeholder="' . $context->placeholder . '"', $context->default) .
        $this->_reset_value($context->reset) . '
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function text($name, $context, $cls)
    {
        $context = (object) array_merge([
            'label' => 'Texte',
            'placeholder' => 'Saisissez votre texte',
            'default' => "Je suis le texte d'un champ de texte",
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label' . (strpos($cls, 'hidden') !== false ? ' style="display:none!important"' : '') . '>
                        <span class="swagg-label-title">' . $context->label . '</span>
                            ' . $this->formBuilderService->formControl('text', $name . '.default', $context->default, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm" placeholder="' . $context->placeholder . '"') .
        $this->_reset_value($context->reset) . '
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);

    }

    public function number($name, $context)
    {
        $context = (object) array_merge([
            'label' => 'Texte',
            'placeholder' => "Entrez une valeur numérique",
            'default' => 0,
            'step' => 1,
            'min' => 0,
            'max' => 100,
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="swagg-label-title">' . $context->label . '</span>
                            ' . $this->formBuilderService->formControl('number', $name . '.default', $context->default, 'data-swagg-widget-name="' . $this->_name . '" step="' . $context->step . '" min="' . $context->min . '" max="' . $context->max . '" class="form-control ' . $context->class . ' input-sm" placeholder="' . $context->placeholder . '"') .
        $this->_reset_value($context->reset) . '
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);

    }

    public function checkbox($name, $context, $type = 'checkbox')
    {
        $context = (object) array_merge([
            'label' => 'Texte',
            'checked' => false,
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="cell-container">
                            <span class="cell cell-use-space">
                            ' . $this->formBuilderService->formControl($type, $name . '.checked', $context->checked, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm"') . '
                            </span>
                            <span class="cell cell-spacer-8"></span>
                            <span class="cell"><span class="swagg-label-title">' . $context->label . '</span></span>' .
        $this->_reset_value($context->reset) . '
                        </span>
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function radio($name, $context)
    {
        $this->checkbox($name, $context, 'radio');
    }

    public function card($name, $context)
    {
        $context = (object) array_merge([
            'cards' => $this->commonWidgetControl('website_cards_templates'),
            'default' => "",
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = $context->cards;

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function select($name, $context, $cls = null)
    {
        $context = (object) array_merge([
            'label' => 'Selection',
            'options' => [
                'option-1' => 'Option 1',
                'option-2' => 'Option 2',
                'option-3' => 'Option 3',
            ],
            'default' => "option-1",
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="cell-container">
                            <span class="cell"><span class="swagg-label-title">' . $context->label . '</span></span>
                            <span class="cell cell-spacer-10"></span>
                            <span class="cell">
                                ' . $this->formBuilderService->formControlSelect($name . '.default', $context->options, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm ' . $cls . '"', $context->default) . '
                            </span> ' .
        $this->_reset_value($context->reset) . '
                        </span>
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function color($name, $context)
    {
        $context = (object) array_merge([
            'label' => 'Couleur',
            'default' => "#000000",
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [
                'Supprimer la couleur', 'transparent',
            ],
        ], $context);

        $widget = '<label>
                        <span class="cell-container">
                            <span class="cell"><span class="swagg-label-title">' . $context->label . '</span></span>
                            <span class="cell cell-spacer"></span>
                            <span class="cell cell-use-space">
                            ' . $this->formBuilderService->formControl('text', $name . '.default', $context->default, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm jquery-minicolors" title="Sélectionne une couleur"') . '
                            </span>
                            ' . $this->_reset_value($context->reset) . '
                        </span>
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function range($name, $context)
    {
        $context = (object) array_merge([
            'label' => 'Taille',
            'step' => 1,
            'min' => 1,
            'max' => 100,
            'default' => 1,
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="swagg-label-title">' . $context->label . '</span>
                        <span class="cell-container">
                            <span class="cell">
                                <input bind-model="' . $name . '" name="' . $name . '.default" type="range" step="' . $context->step . '" min="' . $context->min . '" max="' . $context->max . '" value="' . $context->default . '"></span>
                            <span class="cell cell-spacer-8"></span>
                            <span class="cell cell-25">' . $this->formBuilderService->formControl('number', $name . '.default', $context->default, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm" step="' . $context->step . '" min="' . $context->min . '" max="' . $context->max . '" bind-model="' . $name . '"') . '</span>' .
        $this->_reset_value($context->reset) . '
                        </span>
                    </label>';

        $this->_bindjs_script_data .= "$name:{$context->default},";
        $this->_bindjs_script_callback .= "$name:{dom:'[bind-model=$name]'},";

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);
    }

    public function wysiwyg($name, $context)
    {
        $context = (object) array_merge([
            'label' => '',
            'placeholder' => 'Saisissez votre texte',
            'default' => "Je suis un bloc de texte. Cliquez sur moi pour modifier mon texte.",
            'version' => '',
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="swagg-label-title">' . $context->label . '</span>
                        ' . $this->formBuilderService->formControlTextarea($name . '.default', 'hidden data-swagg-widget-name="' . $this->_name . '"', $context->default)
        . '<textarea class="summernote-editor">' . $context->default . '</textarea>' .
        $this->_reset_value($context->reset) . '
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function image($name, $context)
    {
        $context = (object) array_merge([
            'label' => 'Choisissez une image',
            'default' => $this->swaggPickImage,
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="swagg-label-title">' . $context->label . '</span>
                        <span class="btn_pick_image_wrapper">
                        ' . $this->formBuilderService->formControl('text', $name . '.default', $context->default, 'data-swagg-widget-name="' . $this->_name . '" style="border-radius: 3px 3px 0 0;" class="_hidden form-control input-sm"') . '
                            <button data-js="panel={click:filesBrowser}" class="btn bttn-clean btn_pick_image">
                                <img class="' . $name . '.default" src="' . $context->default . '" />
                                <a data-js="panel={click:resetImage}" class="btn_delete_image">Effacer</a>
                            </button>
                        </span>
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function images($name, $context)
    {
        $context = (object) array_merge([
            'label' => 'Choisissez des images',
            'default' => '{}',
            'selectors' => [],
            'class' => '',
            'data_js' => 'swaggPickMultipleImages={keyup:getImages}',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="swagg-label-title">' . $context->label . '</span>
                        <span class="btn_pick_image_wrapper">
                        ' . $this->formBuilderService->formControlTextarea($name . '.default', '_hidden data-swagg-widget-name="' . $this->_name . '"', $context->default, 'data-js="'. $context->data_js .'"') . '
                        <ul class="swagg-carousel-items" data-js="panel={click:filesBrowserMultiple}"></ul>
                        </span>
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function cardinals($name, $context)
    {
        $context = (object) array_merge([
            'label' => 'Cardinalité',
            'default' => (object) [
                'top' => 0,
                'right' => 0,
                'bottom' => 0,
                'left' => 0,
            ],
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="swagg-label-title">' . $context->label . '</span>
                            <span class="cell-container text-center">
                                <span class="cell">
                                    ' . $this->formBuilderService->formControl('number', $name . '.default.top', $context->default->top, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm" style="border-radius:3px 0 0 3px" bind-model="' . $name . '"') . '
                                        <span class="btn-xs">Haut</span>
                                </span>
                                <span class="cell">
                                    ' . $this->formBuilderService->formControl('number', $name . '.default.right', $context->default->right, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm" style="border-radius:0" bind-model="' . $name . '"') . '
                                        <span class="btn-xs">Droit</span>
                                </span>
                                <span class="cell">
                                    ' . $this->formBuilderService->formControl('number', $name . '.default.bottom', $context->default->bottom, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm" style="border-radius:0" bind-model="' . $name . '"') . '
                                        <span class="btn-xs">Bas</span>
                                </span>
                                <span class="cell">
                                    ' . $this->formBuilderService->formControl('number', $name . '.default.left', $context->default->left, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm" style="border-radius:0" bind-model="' . $name . '"') . '
                                        <span class="btn-xs">Gauche</span>
                                </span>
                                <span class="cell"><button style="border-radius:0 3px 3px 0" type="button" class="btn btn-sm"><i class="fa fa-unlink"></i></button><span class="btn-xs">Lier</span></span>
                            </span>
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function link($name, $context)
    {
        $context = (object) array_merge([
            'label' => 'Lien',
            'placeholder' => 'http://votre-lien.com',
            'default' => '',
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="swagg-label-title">' . $context->label . '</span>
                            <span class="cell-container">
                            <span class="cell">' . $this->formBuilderService->formControl('text', $name . '.default', $context->default, 'data-swagg-widget-name="' . $this->_name . '" class="form-control ' . $context->class . ' input-sm" placeholder="' . $context->placeholder . '" style="border-radius:3px 0 0 3px"') . '</span>
                            <span class="cell cell-use-space"><button data-js="panel={click:showPostsLinksModal}"  title="Parcourir et coller un lien" class="btn btn-sm btn-primary" style="border-radius:0 3px 3px 0"><i class="fa fa-link"></i></button></span>' .
        $this->_reset_value($context->reset) . '
                        </span>
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    public function fontAwesome($name, $context)
    {
        $context = (object) array_merge([
            'label' => 'Icône',
            'default' => 'fa-apple',
            'selectors' => [],
            'class' => '',
            'element_attr' => [],
            'container_attr' => [],
            'reset' => [],
        ], $context);

        $widget = '<label>
                        <span class="swagg-label-title">' . $context->label . '</span>
                        <select name="' . $name . '.default" data-swagg-widget-name="' . $this->_name . '" value="' . $context->default . '" class="form-control ' . $context->class . ' widget-font-awesome"></select>' .
        $this->_reset_value($context->reset) . '
                    </label>';

        $this->_conf_tab($widget, $name, $context);

        $this->_conf_selectors($context);

        $this->_conf_element_attr($context);

        $this->_conf_container_attr($context);
    }

    /*
    END WIDGETS
     */

    /*
    START WIDGETS COMMON
     */
    public function commonWidgetControl($param)
    {
        $common = [

            'alignment' => [
                'label' => 'Alignement',
                'options' => [
                    'left' => 'Gauche',
                    'right' => 'Droite',
                    'center' => 'Centré',
                    'justify' => 'Justifié',
                ],
                'default' => 'text-center',
                'selectors' => [
                    '' => [
                        ['text-align', '{{ alignment.default }}'],
                    ],
                ],
            ],
            'website_pages' => $this->pageService->asOptions(['trash' => false, 'type' => 'page'], null),
            'website_navs' => $this->navService->asOptions(),
            'website_articles' => $this->articleService->asOptions(['trash' => false/* , 'auth' => true */]),
            'website_partials' => $this->dirService->asOptions('templates/partials', 'string', 'html.twig'),
            'website_components' => $this->dirService->asOptions('templates/components', 'string', 'html.twig'),
            'website_cards_templates' => $this->dirService->widgetCards(),
            'website_cards_styles' => $this->dirService->asOptions('templates/cards/css', 'string', 'css'),
        ];

        if (isset($common[$param])) {
            return $common[$param];
        }
        return [];
    }

    public function widgetCommonTypo($w, $selector)
    {
        $w
            ->control('range', 'typoSize', [
                'label' => "Taille (px)",
                'default' => 14,
                'min' => 0,
                'max' => 100,
                'selectors' => [
                    $selector => [
                        ['font-size', '{{ typoSize.default }}', 'px'],
                    ],
                ],
            ])
            ->control('select', 'typoTransform', [
                'label' => 'Transformation',
                'options' => [
                    'initial' => 'Normal',
                    'uppercase' => 'Majuscule',
                    'lowercase' => 'Miniscule',
                    'capitalize' => 'Capitaliser',
                ],
                'default' => 'default',
                'selectors' => [
                    $selector => [
                        ['text-transform', '{{ typoTransform.default }}'],
                    ],
                ],
            ])
            ->control('select', 'typoWeight', [
                'label' => 'Poids',
                'options' => [
                    '100' => '100',
                    '200' => '200',
                    '300' => '300',
                    '400' => '400',
                    '500' => '500',
                    '600' => '600',
                    '700' => '700',
                    '800' => '800',
                    '900' => '900',
                    'inherit' => 'Par défaut',
                    'normal' => 'Normal',
                    'bold' => 'Gras',
                ],
                'default' => 'inherit',
                'selectors' => [
                    $selector => [
                        ['font-weight', '{{ typoWeight.default }}'],
                    ],
                ],
            ])
            ->control('select', 'typoStyle', [
                'label' => 'Style',
                'options' => [
                    'initial' => 'Normal',
                    'italic' => 'Italique',
                    'oblique' => 'Oblique',
                ],
                'default' => 'default',
                'selectors' => [
                    $selector => [
                        ['font-style', '{{ typoStyle.default }}'],
                    ],
                ],
            ])
            ->control('range', 'typoHeight', [
                'label' => "Hauteur de ligne (em)",
                'default' => 1.5,
                'min' => 0.1,
                'step' => 0.1,
                'max' => 10,
                'selectors' => [
                    $selector => [
                        ['line-height', '{{ typoHeight.default }}', 'em'],
                    ],
                ],
            ])
            ->control('range', 'typoSpacing', [
                'label' => "Espacement des lettres (px)",
                'default' => 0,
                'min' => -5,
                'step' => 0.1,
                'max' => 10,
                'selectors' => [
                    $selector => [
                        ['letter-spacing', '{{ typoSpacing.default }}', 'px'],
                    ],
                ],
            ]);

        return $w;
    }

    public function widgetCommonShadow($w, $selector)
    {
        $w
            ->control('color', 'shadowColor', [
                'selectors' => [
                    $selector => [
                        ['text-shadow', '{{ shadowHorizontalPosition.default }}px {{ shadowVerticalPosition.default }}px {{ shadowBlurPosition.default }}px {{ shadowColor.default }}'],
                    ],
                ],
            ])
            ->control('range', 'shadowHorizontalPosition', [
                'label' => 'Position horizontale',
                'default' => 0,
                'min' => -100,
                'max' => 100,
            ])
            ->control('range', 'shadowVerticalPosition', [
                'label' => 'Position verticale',
                'default' => 0,
                'min' => -100,
                'max' => 100,
            ])
            ->control('range', 'shadowBlurPosition', [
                'label' => "Effet de flou",
                'default' => 0,
                'min' => 0,
                'max' => 100,
            ])
        ;
        return $w;
    }

    public function commonAdvancedTab($w)
    {
        $w
            ->controlGroup('Elément', function ($w) {

                $w
                    ->control('range', 'advancedWidth', [
                        'label' => "Largeur du conteneur (%)",
                        'default' => 100,
                        'selectors' => [
                            '' => [
                                ['width', '{{ advancedWidth.default }}%'],
                            ],
                        ],
                    ])
                    ->control('range', 'advancedHorizontalPadding', [
                        'label' => "Rambourages horizontaux internes (px)",
                        'default' => 0,
                        'min' => 0,
                        'max' => 1000,
                        'selectors' => [
                            '' => [
                                ['padding-top', '{{ advancedHorizontalPadding.default }}px'],
                                ['padding-bottom', '{{ advancedHorizontalPadding.default }}px'],
                            ],
                        ],
                    ])
                    ->control('range', 'advancedVerticalPadding', [
                        'label' => "Rambourages verticaux internes (px)",
                        'default' => 0,
                        'min' => 0,
                        'max' => 1000,
                        'selectors' => [
                            '' => [
                                ['padding-left', '{{ advancedVerticalPadding.default }}px'],
                                ['padding-right', '{{ advancedVerticalPadding.default }}px'],
                            ],
                        ],
                    ]);
            })
            ->controlGroup("Attributs d'élément", function ($w) {
                $w
                    ->control('text', 'elementAttr__id__0911', [
                        'label' => 'ID <em>Doit être unique</em>',
                        'default' => '',
                        'placeholder' => "Saisissez l'Identifiant unique",
                        'element_attr' => [
                            'id' => '{{ elementAttr__id__0911.default }}',
                        ],
                    ])
                    ->control('text', 'elementAttr__class__0911', [
                        'label' => 'Classes <em>Séparées par un espace</em>',
                        'default' => '',
                        'placeholder' => 'Saisissez la(les) classe(s)',
                        'element_attr' => [
                            'class' => '{{ elementAttr__class__0911.default }}',
                        ],
                    ])
                    /*->control('text', 'elementAttr__data__0911', [
                        'label' => 'Data Attributs <em>Séparés par un espace</em>',
                        'default' => '',
                        'placeholder' => 'Saisissez la(es) data-attribut(s)',
                        'element_attr' => [
                            'class' => '{{ elementAttr__data__0911.default }}',
                        ],
                    ])*/
                ;
            })
            ->controlGroup("Bordure d'élément", function ($w) {

                $w
                    ->control('range', 'advancedBorderWidth', [
                        'label' => "Epaisseur de bordure (px)",
                        'default' => 0,
                        'min' => 0,
                        'selectors' => [
                            '' => [
                                ['border-width', '{{ advancedBorderWidth.default }}px'],
                            ],
                        ],
                    ])
                    ->control('select', 'advancedBorderStyle', [
                        'label' => 'Style de bordure',
                        'options' => [
                            'solid' => 'Solide',
                            'double' => 'Double',
                            'dotted' => 'Pointillés',
                            'dashed' => 'Tirets',
                        ],
                        'default' => 'solid',
                        'selectors' => [
                            '' => [
                                ['border-style', '{{ advancedBorderStyle.default }}'],
                            ],
                        ],
                    ])
                    ->control('color', 'advancedBorderColor', [
                        'label' => 'Couleur de bordure',
                        'selectors' => [
                            '' => [
                                ['border-color', '{{ advancedBorderColor.default }}'],
                            ],
                        ],
                    ])
                    ->control('range', 'advancedBorderRadius', [
                        'label' => "Courbure de bordure (px)",
                        'default' => 0,
                        'min' => 0,
                        'selectors' => [
                            '' => [
                                ['border-radius', '{{ advancedBorderRadius.default }}px'],
                            ],
                        ],
                    ])
                ;
            })
            ->controlGroup("Arrière-plan d'élément", function ($w) {

                $w
                    ->control('color', 'advancedBgColor', [
                        'default' => 'transparent',
                        'selectors' => [
                            '::before' => [
                                ['background-color', '{{ advancedBgColor.default }}'],
                            ],
                        ],
                    ])
                    ->control('image', 'image1', [
                        'selectors' => [
                            '' => [
                                ['background-image', '{% if image1.default == "' . $this->swaggPickImage . '" %}none{% else %}url({{ image1.default }}){% endif %}'],
                            ],
                        ],
                    ])
                    ->control('select', 'advancedBgAttachment', [
                        'label' => 'Attachement',
                        'options' => [
                            'inherit' => 'Normal',
                            'fixed' => 'Fixer',
                            'initial' => 'Initial',
                        ],
                        'default' => 'inherit',
                        'selectors' => [
                            '' => [
                                ['background-attachment', '{{ advancedBgAttachment.default }}'],
                            ],
                        ],
                    ])
                    ->control('select', 'advancedBgPosition', [
                        'label' => 'Position',
                        'options' => [
                            'inherit' => 'Défaut',
                            'top left' => 'Haut Gauche',
                            'top center' => 'Haut Centrer',
                            'top right' => 'Haut Droite',
                            'center left' => 'Centrer Gauche',
                            'center center' => 'Centrer Centrer',
                            'center right' => 'Centrer Droite',
                            'bottom left' => 'Bas Gauche',
                            'bottom center' => 'Bas Centrer',
                            'bottom right' => 'Bas Droite',
                        ],
                        'default' => 'center center',
                        'selectors' => [
                            '' => [
                                ['background-position', '{{ advancedBgPosition.default }}'],
                            ],
                        ],
                    ])
                    ->control('select', 'advancedBgRepeat', [
                        'label' => 'Répétition',
                        'options' => [
                            'no-repeat' => 'Aucune',
                            'repeat-x' => 'Horizontale',
                            'repeat-y' => 'Verticale',
                            'repeat' => 'Horizontale et Verticale',
                        ],
                        'default' => 'no-repeat',
                        'selectors' => [
                            '' => [
                                ['background-repeat', '{{ advancedBgRepeat.default }}'],
                            ],
                        ],
                    ])
                    ->control('select', 'advancedBgSize', [
                        'label' => 'Taille',
                        'options' => [
                            'auto' => 'Automatique',
                            'contain' => 'Contenir',
                            'cover' => 'Couvrir',
                            'inherit' => 'Défaut',
                        ],
                        'default' => 'cover',
                        'selectors' => [
                            '' => [
                                ['background-size', '{{ advancedBgSize.default }}'],
                            ],
                        ],
                    ])
                ;
            })
            ->controlGroup("Ombrage d'élément", function ($w) {

                $w
                    ->control('color', 'advancedShadowColor', [
                        'selectors' => [
                            '' => [
                                ['box-shadow', '{{ advancedTypeShadow.default }} {{ advancedHorizontalShadow.default }}px {{ advancedVerticalShadow.default }}px {{ advancedBlurShadow.default }}px {{ advancedSpreadShadow.default }}px {{ advancedShadowColor.default }}'],
                            ],
                        ],
                    ])
                    ->control('range', 'advancedHorizontalShadow', [
                        'label' => 'Position horizontale',
                        'default' => 0,
                        'min' => -100,
                        'max' => 100,
                    ])
                    ->control('range', 'advancedVerticalShadow', [
                        'label' => 'Position verticale',
                        'default' => 0,
                        'min' => -100,
                        'max' => 100,
                    ])
                    ->control('range', 'advancedBlurShadow', [
                        'label' => "Effet de flou",
                        'default' => 0,
                        'min' => 0,
                        'max' => 100,
                    ])
                    ->control('range', 'advancedSpreadShadow', [
                        'label' => "Propagation",
                        'default' => 0,
                        'min' => 0,
                        'max' => 100,
                    ])
                    ->control('select', 'advancedTypeShadow', [
                        'label' => "Type",
                        'options' => [
                            'inset' => 'Interne',
                            '' => 'Externe',
                        ],
                        'default' => '',
                    ])
                ;
            })
        ;
    }

    public function controlGroupCommonBootstrapColumns($w)
    {
        $w
            ->controlGroup('<span title="Nombre de colonnes">Responsivité (Nbre de colonnes)</span>', function ($w) {

                $w
                    ->control('range', 'colmd', [
                        'label' => 'Desktop <em>col-md- ? </em>',
                        'default' => 3,
                        'max' => 12,
                        'min' => 1
                    ])
                    ->control('range', 'colsm', [
                        'label' => 'Tablette <em>col-sm- ? </em>',
                        'default' => 6,
                        'max' => 12,
                        'min' => 1
                    ])
                    ->control('range', 'colxs', [
                        'label' => 'Smartphone <em>col-xs- ? </em>',
                        'default' => 12,
                        'max' => 12,
                        'min' => 1
                    ])
                ;
            })
            ;
        return $w;
    }

    public function controlGroupCommonSelectCard($w)
    {
        $w
            ->controlGroup('Sélectionner une <em>Card</em>', function ($w) {
                $w
                    ->control('card', 'card')
                ;
            })
            ;
        return $w;
    }

    public function commonHowManyPosts($w)
    {
        $w
            ->control('number', 'perPage', [
                'label' => 'Nombre de posts',
                'default' => 15,
                'min' => 1,
            ])
            ->control('select', 'paginate', [
                'label' => 'Afficher la pagination',
                'options' => [
                    true => 'Oui',
                    false => 'Non'
                ],
                'default' => true
            ])
            ;
        return $w;
    }

    /*
    END WIDGETS COMMON
     */

    private function _conf_tab($widget, $name, $context)
    {
        if ($this->_which_tab == 'content') {
            $this->_content_tab_controls_group[$this->_which_tab][$this->_group_title][] = $widget;
            $this->_content_tab_controls_context[$name] = $context;

        } else if ($this->_which_tab == 'style') {
            $this->_style_tab_controls_group[$this->_which_tab][$this->_group_title][] = $widget;
            $this->_style_tab_controls_context[$name] = $context;

        } else {
            $this->_advanced_tab_controls_group[$this->_which_tab][$this->_group_title][] = $widget;
            $this->_advanced_tab_controls_context[$name] = $context;
        }

    }

    private function _conf_selectors($context)
    {
        foreach ($context->selectors as $selector => $props_and_unit_and_class) {

            $selector = trim($selector);

            $this->_selectors[$selector][] = [
                'props_and_unit_and_class' => $props_and_unit_and_class,
                'default' => $context->default,
            ];
        }
    }

    private function _conf_element_attr($context)
    {
        foreach ($context->element_attr as $prop => $val) {
            if ($prop === 'id') {
                $this->_element_id = $val;
            }
            if ($prop === 'class') {
                $this->_element_class .= $val . ' ';
            }
        }
        $this->_element_attr = (object) [
            'element_id' => trim($this->_element_id),
            'element_class' => trim($this->_element_class),
        ];
    }

    private function _conf_container_attr($context)
    {
        foreach ($context->container_attr as $prop => $val) {
            if ($prop === 'id') {
                $this->_container_id = $val;
            }
            if ($prop === 'class') {
                $this->_container_class .= $val . ' ';
            }
        }
        $this->_container_attr = (object) [
            'container_id' => trim($this->_container_id),
            'container_class' => trim($this->_container_class),
        ];
    }

    private function _conf_css_rules()
    {
        if (isset($this->_selectors)) {

            $css_rules = [];

            foreach ($this->_selectors as $selector => $data) {

                $css_rules[$selector] = '';

                foreach ($data as $i => $props_and_unit_and_class_and_default) {

                    if ($i == 0) {
                        //in case of ::before or :before
                        if (strpos($selector, ':') === false) {
                            
                            if ($this->_name === 'Section' || $this->_name === 'SectionWidget') { // retrocompatibility
                                $s = '.swagg-section-{{ id }} {';
                            } else if ($this->_name === 'Column' || $this->_name === 'ColumnWidget') { // retrocompatibility
                                $s = '.swagg-col-{{ id }} {';
                            } else {
                                $s = ".swagg-element-wrapper-{{ id }} " . $selector . "{";
                            }

                            $css_rules[$selector] .= $s;

                        } else {

                            if ($this->_name === 'Section' || $this->_name === 'SectionWidget') { // retrocompatibility
                                $s = ".swagg-section-{{ id }}" . $selector . "{";
                            } else if ($this->_name === 'Column' || $this->_name === 'ColumnWidget') { // retrocompatibility
                                $s = ".swagg-col-{{ id }}" . $selector . "{";
                            } else {
                                $s = ".swagg-element-wrapper-{{ id }}" . $selector . "{";
                            }

                            $css_rules[$selector] .= $s;
                        }
                    }

                    foreach ($props_and_unit_and_class_and_default['props_and_unit_and_class'] as $props_and_unit_and_class) {

                        $prop = $props_and_unit_and_class[0];
                        $val = $props_and_unit_and_class_and_default['default'];
                        $unit = '';

                        if (isset($props_and_unit_and_class[1])) {
                            $val = $props_and_unit_and_class[1];
                        }

                        if (isset($props_and_unit_and_class[2])) {
                            $unit = $props_and_unit_and_class[2];
                        }

                        $css_rules[$selector] .= $prop . ':' . $val . $unit . ';';
                    }

                    if ($i == sizeof($data) - 1) {
                        $css_rules[$selector] .= "}";
                        $css_rules[$selector] = str_ireplace(';}', '}', $css_rules[$selector]);
                    }
                }
            }

            $this->_css_rules .= str_ireplace(['} .', ' }}.'], ['}.', ' }} .'], $this->please->getBundleService('string')->getArrayToString($css_rules));
        }
    }

    private function _reset_value($reset_context)
    {
        if (isset($reset_context[0]) && isset($reset_context[0])) {
            $title = $reset_context[0];
            $reset_value = $reset_context[1];
            return '<span class="cell cell-use-space"><button data-js="panel={click:resetControlValue}" data-reset-value="' . $reset_value . '" title="' . $title . '" class="btn bttn-clean btn-xs" type="button"><i class="fa fa-remove" /></button></span>';
        }
    }

    private function trim_all($string)
    {
        if( !is_null($string) ){
            return str_ireplace(['{{ ', ' }}'], ['{{ ', ' }}'], $this->please->getBundleService('string')->getTrimAll($string));
        }
        return '';
    }
    
    public function controller(callable $callback)
    {
        if( !is_null($this->_ctrlerExecWatcher) ){

            // because of fluent chaining
            // we cant return the callback result
            // but allways the current object ( $this )
            // so lets store the result
            // result that will be next got from
            // DovStone\Bundle\BlogAdminBundle\Service->buildDynamicWidget::function
            $result = $callback($this->please, (object) [

                //
                'adminRepository' => $this->__Repositories->AdminRepository,
                'adminRepo' => $this->__Repositories->AdminRepository,
                //
                'navRepository' => $this->__Repositories->NavRepository,
                'navRepo' => $this->__Repositories->NavRepository,
                //
                'postRepository' => $this->__Repositories->PostRepository,
                'postRepo' => $this->__Repositories->PostRepository,
                //
                'sectionRepository' => $this->__Repositories->SectionRepository,
                'sectionRepo' => $this->__Repositories->SectionRepository

            ]);

            $this->please->setGlobal([ "{$this->_name}WidgetControllerResult" => $result ]);
        }

        //
        return $this;
    }

    public function renderWidget(callable $callback)
    {
        if( is_null($this->_ctrlerExecWatcher) ){

            $this->_conf_css_rules();

            $context = array_merge($this->_content_tab_controls_context, $this->_style_tab_controls_context);

            //
            $dynamicAttributes = '{}';
            //
            $dynamicText = "<p class='widget-data-name'><b>{$this->_titleText}</b></p>";

            foreach($context as $name => $contextContent) {
                //
                $sanitizedName = str_ireplace('-', '', strtolower($name));
                $dynamicAttributes .= '"'.$sanitizedName.'":"{{ '.$name.'.default }}",';
                //
                foreach($contextContent as $k => $v){
                    if( $k === 'label' ){
                        $dynamicText .= "<p data-name='$name.default'><b>$v</b><span>{{ $name.default }}</span></p>";
                    }
                }
            }
            //
            $dynamicAttributes .= '}';
            $context['dynamicAttributes'] = str_ireplace(['",}', "='{}"], ['"}', "='{"], "data-swagg-dynamic-context='$dynamicAttributes'");
            //
            $context['dynamicText'] = $dynamicText;
            
            $tabs = ['content', 'style', 'advanced'];

            foreach ($tabs as $tabName) {

                $controlsGroup = "_" . $tabName . "_tab_controls_group";
                $groupBuilt = "_" . $tabName . "_tab_controls_group_built";

                if (isset($this->$controlsGroup[$tabName])) {

                    foreach ($this->$controlsGroup[$tabName] as $group_title => $controls) {
                        $this->$groupBuilt .= '<div class="swagg-toggle-wrapper open"><header class="swagg-toggle-header"><button data-js="panel={click:toggleTab}" class="btn bttn-clean">' . $group_title . '</button></header><div class="swagg-toggle-body">';
                        foreach ($controls as $control) {
                            $this->$groupBuilt .= $control;
                        }
                        $this->$groupBuilt .= '</div></div>';
                    }
                }
            }

            $r = '<li
                        data-swagg-widget-name="' . $this->_name . '"
                        data-js="panel={mouseover:mouseHover;mouseout:mouseOut}">';

            $r .= '<div class="swagg-panel-element unselectable">';
            $r .= $this->_icon;
            $r .= $this->_title;
            $r .= '</div>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="content-tab" type="text/template7">';
            $r .= $this->_content_tab_controls_group_built;
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="style-tab" type="text/template7">';
            $r .= $this->_style_tab_controls_group_built;
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="advanced-tab" type="text/template7">';
            $r .= $this->_advanced_tab_controls_group_built;
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="ctx" type="text/template7">';
            $r .= json_encode($context);
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="css-rules" type="text/template7">';
            $r .= $this->_css_rules;
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="css" type="text/template7">';
            $r .= $this->_stylesheet;
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="element-attr" type="text/template7">';
            $r .= json_encode($this->_element_attr);
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="container-attr" type="text/template7">';
            $r .= json_encode($this->_container_attr);
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="js" type="text/template7">';
            $r .= $this->_javascript;
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="bind-js" type="text/bindjs">';
            $r .= "Bind({{$this->_bindjs_script_data}},{{$this->_bindjs_script_callback}})";
            $r .= '</script>';

            $r .= '<script data-dont-reload-asset="true" data-tmpl="view" type="text/template7">
                    <div
                        data-swagg-element-id="{{ id }}"
                        data-swagg-widget-name="' . $this->_name . '"
                        class="swagg-element swagg-highlighted hidden">
                            <div data-js="element={click:showTabWrapper}" class="swagg-element-wrapper-{{ id }} swagg-element-wrapper">'
            . $this->trim_all($callback($context = (object) $context)) .
                '</div>
                            </div>
                        </script>';

            $r .= '</li>';

            return $r;
        }

        return $this;
    }
}
