<?php
/*------------------------------------------------------------------------
# author    lomart
# copyright : Copyright (C) 2011 lomart.fr All Rights Reserved.
# @license  : http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website   : http://lomart.fr
# Technical Support:  Forum - http://forum.joomla.fr
-------------------------------------------------------------------------*/
// no direct access
defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

class JFormFieldTitle extends JFormField
{
	protected $type = 'Title';

	protected function getInput() 
	{
	   return ' ';
  }
  
	protected function getLabel()
	{
    $html = array();
    
    // texte a afficher 
       
    $label = $this->element['label'];
		$label = $this->translateLabel ? JText::_($label) : $label;
    
    // param‚tre 'style' pour remplacer le style par d‚faut
    // a indiquer dans le xml sous la forme :
    // style="margin:5px;border:1px dotted red"
    // style="REDBOX" qui retourne le contenu de la cl‚ REDBOOX="margin:5px;border:1px dotted red"
     
    $style = $this->element['style']; 
		$style = $this->translateLabel ? JText::_($style) : $style;
		
		// contruction de la chaine retour
    $html[] = "<div style='";
    $html[] = $style;
    $html[] = "display:block;clear:both;'>";
    $html[] = $label;
    $html[] = "</div>";

    return implode('',$html);

	}
}
