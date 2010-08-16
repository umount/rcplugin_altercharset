<?php

/**
 * Alter charset headers, for change of the coding of the message
 *
 *
 * Enable the plugin in config/main.inc.php and charset to encoding :
 *   $rcmail_config['alter_charset'] = array('win'=>'WINDOWS-1251', 'utf'=>'UTF-8','koi'=>'KOI8-R', 'iso'=> 'ISO-8859-5');
 *
 * @version 1.0
 * @author Denis Sobolev
 * @website http://github.com/umount/rcplugin_altercharset
 */
class alter_charset extends rcube_plugin
{
  public $task = 'mail';

  function init()
  {
    $rcmail = rcmail::get_instance();

    if($alias_charset = get_input_value('_alter_charset', RCUBE_INPUT_GET)){
      $alter_charset = (array)$rcmail->config->get('alter_charset', array());
      $charset = $alter_charset[$alias_charset];
      $this->add_hook('message_part_after', array($this, 'change_charset'));
    }
    $this->register_action('plugin.alter_charset', array($this, 'change_charset'));
    if ($rcmail->action == 'show' || $rcmail->action == 'preview') {
      $this->add_hook('message_headers_output', array($this, 'header_alter_charsets'));
    }
  }

  function header_alter_charsets($p) {
    if ($msg_uid = get_input_value('_uid', RCUBE_INPUT_GET)){
      $rcmail = rcmail::get_instance();
      $alter_charset = (array)$rcmail->config->get('alter_charset', array());

      $charset = $this->charset($msg_uid);

      $url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

      //error_log($charset."\n",3,"/var/log/nginx/checkmail_error.log");

      if($alias_charset = get_input_value('_alter_charset', RCUBE_INPUT_GET)){
        $charset = $alter_charset[$alias_charset];
        $url = str_replace('&_alter_charset='.$alias_charset, '', $url);
      }

      $selector = '';
      foreach ($alter_charset as $key => $value) {
        $selector .=
          sprintf('<input type="radio" id="r_%s" onclick="window.location.href=\'https://%s&_alter_charset=%s\';" value="%s" %s>',
            $key,$url,$key,$key,(current(array_keys($alter_charset,$charset)) == $key)?'checked':'').
          html::label($attrib['id'], strtolower($value));
      }
      $p['output']['selectcharset'] = array('title' => rcube_label('charset'), 'value' => strtr($selector, "\r\n", "  "));
    }
    return $p;
  }

  private function charset($msg_uid){
      $rcmail = rcmail::get_instance();

      $headers = $rcmail->imap->get_headers($msg_uid);
      $charset = strtoupper($headers->charset);

      if (!$charset) {
        $struct = $rcmail->imap->get_structure($msg_uid, $rcmail->headers->body_structure);
        if (!empty($struct->parts[0]->parts[0]->charset))
          $charset = strtoupper($struct->parts[0]->parts[0]->charset);
      }
      if (!$charset)
        $charset = strtoupper($rcmail->output->get_charset());
      return $charset;
  }

  function change_charset($args) {
    if ($msg_uid = get_input_value('_uid', RCUBE_INPUT_GET)){

      $rcmail = rcmail::get_instance();
      $alter_charset = (array)$rcmail->config->get('alter_charset', array());
      $headers = $rcmail->imap->get_headers($msg_uid);

      if($alias_charset = get_input_value('_alter_charset', RCUBE_INPUT_GET)){
        $output_charset = $alter_charset[$alias_charset];
      }

      $input_charset = $rcmail->output->get_charset();
      $charset = $this->charset($msg_uid);

      $msg_body = rcube_charset_convert($args[body], $input_charset, $charset);
      $args['body'] = rcube_charset_convert($msg_body, $output_charset);

      return $args;
    }
  }
}
