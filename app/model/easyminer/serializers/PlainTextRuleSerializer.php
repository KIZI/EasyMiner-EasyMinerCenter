<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\Task;
use EasyMinerCenter\Model\EasyMiner\Entities\User;

/**
 * Class PlainTextRuleSerializer
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class PlainTextRuleSerializer{

  /**
   * @param Rule[] $rules
   * @param User|null $user = null
   * @param RuleSet|Task|null $topEntity = null
   * @param string $modeText=''
   * @return string
   */
  public static function serialize($rules, $user=null, $topEntity=null, $modeText=''){
    $result='';
    if ($topEntity instanceof RuleSet){
      $result.='Rule set: '.$topEntity->name."\nRules count: ".$topEntity->rulesCount."\n";
    }elseif($topEntity instanceof Task){
      $result.='Task: '.$topEntity->name."\nRules count: ".$topEntity->rulesCount."\n";
      $result.='Rules in Rule clipboard: '.$topEntity->rulesInRuleClipboardCount."\n";
    }
    $result.='Exported: '.date('Y-m-d H:i:s');
    if ($user instanceof User){
      $result.=', '.$user->name.' ('.$user->email.')';
    }
    if (!empty($modeText)){
      $result.="\nExport mode: ".$modeText;
    }
    $result.="\n\n";
    if (!empty($rules)){
      foreach ($rules as $rule){
        $result.=$rule->text.' [conf='.$rule->confidence.',supp='.$rule->support.']'."\n";
      }
    }
    return $result;
  }

}