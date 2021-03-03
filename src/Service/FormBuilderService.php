<?php

namespace DovStone\Bundle\BlogAdminBundle\Service;

use DovStone\Bundle\BlogAdminBundle\Service\PleaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FormBuilderService extends AbstractController
{
    public function __construct(PleaseService $please)
    {
        $this->please = $please;
        //
    }

    public function formControl($type, $name, $valeur, $attr = null, $champ_requis_texte = null)
    {
        if (
               $type == 'text'
            || $type == 'email'
            || $type == 'password'
            || $type == 'radio'
            || $type == 'number'
            || $type == 'submit'
            || $type == 'file'
            || $type == 'checkbox'
            || $type == 'hidden'
        ) {

            $input = '<input name="' . $name . '" type="' . $type . '" value="' . $valeur . '" ' . ($type == 'checkbox' && ($valeur === true || $valeur == 'yes') ? 'checked="checked" ' : '') . $attr . ' />';

            /*
                EX :
                champs_requis([
                    'post_title' => "Le titre de l'article est obligatoire"
                ]);
            */
            if (!is_null($champ_requis_texte) && flash('champs_requis') && isset(json_decode(flash('champs_requis'))->error->$name)) {

                $input .= '<em class="text-danger champ-requis"><i class="fa fa-minus-circle"></i>' . json_decode(flash('champs_requis'))->error->$name . '</em>';
            }

            return $input;
        }

        return '';
    }

    public function formControlSelect($name, $options, $attr = '', $valToSelect = null)
    {
        $select = '<select name="' . $name . '" ' . $attr . '>';

        if(!is_string($options)){
            foreach ($options as $valeur => $option) {
                $select .= '<option' . ($valeur == $valToSelect ? ' selected="selected" ' : ' ') . 'value="' . (is_null($valeur) ? '' : $valeur) . '">' . $option . '</option>';
            }
            $select .= "</select>";
        }
        else {
            $select .= $options;
        }

        $select .= "</select>";

        return $select;
    }

    public function formControlTextarea($name, $attr = '', $valeur = '')
    {
        return '<textarea name="' . $name . '" ' . $attr . '>' . $valeur . '</textarea>';
    }
}