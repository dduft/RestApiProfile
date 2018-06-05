<?php namespace ProcessWire;

require_once dirname(__FILE__) . "/../ApiHelper.php";

class ContactController {
  const AUTH = false;

  public static function mail($params) {
    $options = [
      'formfield_first_name|text',
      'formfield_last_name|text',
      'formfield_email|text',
      'formfield_message|textarea'
    ];
    $params = ApiHelper::checkAndSanitizeRequiredParameters($params, $options);
    $message = self::buildMessage($params, $options);

    $numSent = self::sendMail('ALLFRED-Kontakt', $message);

    return $numSent;
  }

  public static function want_help($params) {
    $options = [
      'formfield_birthday|text',
      'formfield_email|email',
      'formfield_first_name|text',
      'formfield_found_out|text',
      'formfield_gender|text',
      'formfield_last_name|text',
      'formfield_location|text',
      'formfield_want_help|minArray',
      'formfield_street|text',
      'formfield_zip|int'
    ];
    $params = ApiHelper::checkAndSanitizeRequiredParameters($params, $options);
    $message = self::buildMessage($params, $options);

    $numSent = self::sendMail('ALLFRED-ich-moechte-helfen-bewerbungsformular', $message);

    return $numSent;
  }

  public static function need_help($params) {
    $options = [
      'formfield_birthday|text',
      'formfield_email|email',
      'formfield_first_name|text',
      'formfield_found_out|text',
      'formfield_gender|text',
      'formfield_last_name|text',
      'formfield_location|text',
      'formfield_need_help|minArray',
      'formfield_street|text',
      'formfield_zip|int'
    ];
    $params = ApiHelper::checkAndSanitizeRequiredParameters($params, $options);
    $message = self::buildMessage($params, $options);

    $numSent = self::sendMail('ALLFRED-ich-benoetige-hilfe-anmeldeformular', $message);

    return $numSent;
  }

  private static function buildMessage($params, $options) {
    $message = "";
    foreach ($options as $option) {
      $fieldname = explode('|', $option)[0];
      $name = str_replace('formfield_', '', $fieldname);
      $sanitizer = explode('|', $option)[1];
      if($sanitizer == 'minArray') {
        $array = $params->$fieldname;
        $value = "";
        foreach ($array as $val) {
          $value .= $val['value'] . ',';
        }
        $message .= $value . "\n";
      } else {
        $message .= $name . ': ' . $params->$fieldname . "\n";
      }
    }
    return $message;
  }

  private static function sendMail($subject, $message) {
    $m = wire('mail')->new();
    $m->to('office@allfred.at');
    $m->from('office@allfred.at');
    $m->subject($subject);
    $m->body($message);
    $numSent = $m->send();

    return $numSent;
  }
}
