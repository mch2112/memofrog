<?php

namespace SendGrid;

class Mail
{

  private $to_list,
          $from,
          $from_name,
          $reply_to,
          $cc_list,
          $bcc_list,
          $subject,
          $text,
          $html,
          $attachment_list,
          $header_list = array();

  protected $use_headers;

  public function __construct()
  {
    $this->from_name = false;
    $this->reply_to = false;
    $this->setCategory("google_sendgrid_php_lib");
  }

  /**
   * _removeFromList
   * Given a list of key/value pairs, removes the associated keys
   * where a value matches the given string ($item)
   *
   * @param $list array
   * @param $item string
   */
  private function _removeFromList(&$list, $item, $key_field = null)
  {
    foreach ($list as $key => $val)
    {
      if($key_field)
      {
        if($val[$key_field] == $item)
        {
          unset($list[$key]);
        }
      }
      else
      {
        if ($val == $item)
        {
          unset($list[$key]);
        } 
      }
    }
    // repack the indices
    $list = array_values($list);
  }
  
  /**
   * getTos
   * Return the list of recipients
   *
   * @return array
   */
  public function getTos()
  {
    return $this->to_list;
  }
  
  /**
   * setTos
   * Initialize an array for the recipient 'to' field
   * Destroy previous recipient 'to' data.
   *
   * @param $email_list array
   * @return Mail
   */
  public function setTos(array $email_list)
  { 
    $this->to_list = $email_list;

    return $this;
  }
  
  /**
   * setTo
   * Initialize a single email for the recipient 'to' field
   * Destroy previous recipient 'to' data.
   *
   * @param $email string
   * @return Mail
   */
  public function setTo($email)
  {
    $this->to_list = array($email);

    return $this;
  }
  
  /**
   * addTo
   * append an email address to the existing list of addresses
   * Preserve previous recipient 'to' data.
   *
   * @param $email String
   * @return Mail
   */
  public function addTo($email, $name=null)
  {
    $this->to_list[] = ($name ? $name . "<" . $email . ">" : $email);
   
    return $this;
  }
  
  /**
   * removeTo
   * remove an email address from the list of recipient addresses
   *
   * @param $search_term String           the regex value to be removed
   * @return Mail
   */
  public function removeTo($search_term)
  {
    $this->to_list = array_values(array_filter($this->to_list, function($item) use($search_term) {
      return !preg_match("/" . $search_term . "/", $item);
    }));

    return $this;
  }
  
  /**
   * getFrom
   * get the from email address
   *
   * @param  $as_array bool
   * @return string                     the from email address
   */
  public function getFrom($as_array = false)
  {
    if ($as_array && ($name = $this->getFromName())) {
      return array("$this->from" => $name);
    } else {
      return $this->from;
    }
  }
  
  /**
   * setFrom
   * set the from email
   *
   * @param $email String
   * @return Mail          the SendGrid\Mail object.
   */
  public function setFrom($email)
  {
    $this->from = $email;

    return $this;
  }

  /**
   * getFromName
   * get the from name 
   *
   * @return string the from name
   */
  public function getFromName()
  {
    return $this->from_name;
  }

  /**
   * setFromName
   * set the name appended to the from email
   *
   * @param $name String
   * @return Mail        the SendGrid\Mail object.
   */
  public function setFromName($name)
  {
    $this->from_name = $name;

    return $this;
  }

  /**
   * getReplyTo
   * get the reply-to address
   *
   * @return string the reply to address
   */
  public function getReplyTo()
  {
    return $this->reply_to;
  }

  /**
   * setReplyTo
   * set the reply-to address
   *
   * @param  String         $email  the email to reply to
   * @return Mail          the SendGrid\Mail object.
   */
  public function setReplyTo($email)
  {
    $this->reply_to = $email;

    return $this;
  }
  /**
   * getCc
   * get the Carbon Copy list of recipients
   *
   * @return array
   */
  public function getCcs()
  {
    return $this->cc_list;
  }
  
  /**
   * setCcs
   * Set the list of Carbon Copy recipients
   *
   * @param $email_list array  a list of email addresses
   * @return Mail          the SendGrid\Mail object.
   */
  public function setCcs(array $email_list)
  {
    $this->cc_list = $email_list;

    return $this;
  }
  
  /**
   * setCc
   * Initialize the list of Carbon Copy recipients
   * destroy previous recipient data
   *
   * @param  String         $email  a list of email addresses
   * @return Mail          the SendGrid\Mail object.
   */
  public function setCc($email)
  {
    $this->cc_list = array($email);

    return $this;
  }
  
  /**
   * addCc
   * Append an address to the list of Carbon Copy recipients
   *
   * @param  String         $email  an email address
   * @return Mail          the SendGrid\Mail object.
   */
  public function addCc($email)
  {
    $this->cc_list[] = $email;

    return $this;
  }
  
  /**
   * removeCc
   * remove an address from the list of Carbon Copy recipients
   *
   * @param  String        $email  an email address
   * @return Mail         the SendGrid\Mail object.
   */
  public function removeCc($email)
  {
    $this->_removeFromList($this->cc_list, $email);

    return $this;
  }

  /**
   * getBccs
   * return the list of Blind Carbon Copy recipients
   *
   * @return array
   */
  public function getBccs()
  {
    return $this->bcc_list;
  }
  
  /**
   * setBccs
   * set the list of Blind Carbon Copy Recipients
   *
   * @param  array         $email_list  the list of email recipients to
   * @return Mail              the SendGrid\Mail object.
   */
  public function setBccs($email_list)
  {
    $this->bcc_list = $email_list;

    return $this;
  }
  
  /**
   * setBcc
   * Initialize the list of Carbon Copy recipients
   * destroy previous recipient Blind Carbon Copy data
   *
   * @param  String        $email  an email address
   * @return Mail         the SendGrid\Mail object.
   */
  public function setBcc($email)
  {
    $this->bcc_list = array($email);

    return $this;
  }
  
  /**
   * addBcc
   * Append an email address to the list of Blind Carbon Copy recipients
   * 
   * @param  String         $email  an email address
   * @return Mail          the SendGrid\Mail object.
   */
  public function addBcc($email)
  {
    $this->bcc_list[] = $email;

    return $this;
  }

  /** 
   * removeBcc
   * remove an email address from the list of Blind Carbon Copy addresses
   * 
   * @param  String         $email  the email to remove
   * @return Mail          the SendGrid\Mail object.
   */
  public function removeBcc($email)
  {
    $this->_removeFromList($this->bcc_list, $email);

    return $this;
  }

  /** 
   * getSubject
   * get the email subject
   *
   * @return string
   */
  public function getSubject()
  {
    return $this->subject;
  }

  /** 
   * setSubject
   * set the email subject
   *
   * @param  String        $subject  the email subject
   * @return Mail           the SendGrid\Mail object.
   */
  public function setSubject($subject)
  {
    $this->subject = $subject;

    return $this;
  }

  /** 
   * getText
   * get the plain text part of the email
   *
   * @return string
   */
  public function getText()
  {
    return $this->text;
  }

  /** 
   * setText
   * Set the plain text part of the email
   *
   * @param $text string the plain text of the email
   * @return Mail   the SendGrid\Mail object.
   */
  public function setText($text)
  {
    $this->text = $text;

    return $this;
  }
  
  /** 
   * getHtml
   * Get the HTML part of the email
   *
   * @return Mail         the SendGrid\Mail object.
   */
  public function getHtml()
  {
    return $this->html;
  }

  /** 
   * setHTML
   * Set the HTML part of the email
   *
   * @param  String         $html  the HTML part of the email
   * @return Mail         the SendGrid\Mail object.
   */
  public function setHtml($html)
  {
    $this->html = $html;

    return $this;
  }

  /**
   * getAttachments
   * Get the list of file attachments
   *
   * @return array
   */
  public function getAttachments()
  {
    return $this->attachment_list;
  }

  /**
   * setAttachments
   * add multiple file attachments at once
   * destroys previous attachment data.
   *
   * @param  $files array
   * @return Mail
   */
  public function setAttachments(array $files)
  {
    $this->attachment_list = array();
    foreach($files as $file)
    {
      $this->addAttachment($file);
    }

    return $this;
  }

  /**
   * setAttachment
   * Initialize the list of attachments, and add the given file
   * destroys previous attachment data.
   *
   * @param  String         $file  the file to attach
   * @return Mail         the SendGrid\Mail object.
   */
  public function setAttachment($file)
  {
    $this->attachment_list = array($this->_getAttachmentInfo($file));

    return $this;
  }

  /**
   * addAttachment
   * Add a new email attachment, given the file name.
   *
   * @param  String         $file  The file to attach.
   * @return Mail         the SendGrid\Mail object.
   */
  public function addAttachment($file)
  {
    $this->attachment_list[] = $this->_getAttachmentInfo($file);

    return $this;
  }

  /**
   * removeAttachment
   * Remove a previously added file attachment, given the file name.
   *
   * @param  String         $file  the file attachment to remove.
   * @return Mail         the SendGrid\Mail object.
   */
  public function removeAttachment($file)
  {
    $this->_removeFromList($this->attachment_list, $file, "file");

    return $this;
  }

  /**
   * get file details
   *
   * @return array
   */
  private function _getAttachmentInfo($file)
  {
    $info = pathinfo($file);
    $info['file'] = $file;

    return $info;
  }

  /** 
   * setCategories
   * Set the list of category headers
   * destroys previous category header data
   *
   * @param $category_list array
   * @return Mail
   */
  public function setCategories($category_list)
  {
    $this->header_list['category'] = $category_list;
    $this->addCategory('google_sendgrid_php_lib');

    return $this;
  }

  /** 
   * setCategory
   * Clears the category list and adds the given category
   *
   * @param $category string
   * @return Mail
   */
  public function setCategory($category)
  {
    $this->header_list['category'] = array($category);
    $this->addCategory('google_sendgrid_php_lib');

    return $this;
  }

  /** 
   * addCategory
   * Append a category to the list of categories
   *
   * @param $category string
   * @return Mail
   */
  public function addCategory($category)
  {
    $this->header_list['category'][] = $category;

    return $this;
  }

  /** 
   * removeCategory
   * Given a category name, remove that category from the list
   * of category headers
   *
   * @param $category string
   * @return Mail
   */
  public function removeCategory($category)
  {
    $this->_removeFromList($this->header_list['category'], $category);

    return $this;
  }

  /** 
   * SetSubstitutions
   *
   * Substitute a value for list of values, where each value corresponds
   * to the list emails in a one to one relationship. (IE, value[0] = email[0], 
   * value[1] = email[1])
   *
   * @param $key_value_pairs array
   * @return Mail
   */
  public function setSubstitutions($key_value_pairs)
  {
    $this->header_list['sub'] = $key_value_pairs;

    return $this;
  }

  /** 
   * addSubstitution
   * Substitute a value for list of values, where each value corresponds
   * to the list emails in a one to one relationship. (IE, value[0] = email[0], 
   * value[1] = email[1])
   *
   * @param $from_value string
   * @param $to_values array
   * @return Mail
   */
  public function addSubstitution($from_value, array $to_values)
  {
    $this->header_list['sub'][$from_value] = $to_values;

    return $this;
  }

  /** 
   * setSection
   * Set a list of section values
   *
   * @param $key_value_pairs array
   * @return Mail
   */
  public function setSections(array $key_value_pairs)
  {
    $this->header_list['section'] = $key_value_pairs;

    return $this;
  }
  
  /** 
   * addSection
   * append a section value to the list of section values
   *
   * @param $from_value String
   * @param $to_value String
   * @return Mail
   */
  public function addSection($from_value, $to_value)
  {
    $this->header_list['section'][$from_value] = $to_value;

    return $this;
  }

  /** 
   * setUniqueArguments
   * Set a list of unique arguments, to be used for tracking purposes
   *
   * @param $key_value_pairs array
   * @return Mail
   */
  public function setUniqueArguments(array $key_value_pairs)
  {
    $this->header_list['unique_args'] = $key_value_pairs;

    return $this;
  }
    
  /**
   * addUniqueArgument
   * Set a key/value pair of unique arguments, to be used for tracking purposes
   *
   * @param $key string
   * @param $value string
   * @return Mail
   */
  public function addUniqueArgument($key, $value)
  {
    $this->header_list['unique_args'][$key] = $value;

    return $this;
  }

  /**
   * setFilterSettings
   * Set filter/app settings
   *
   * @param  array          $filter_settings  array of fiter settings
   * @return Mail                    the SendGrid\Mail object.
   */
  public function setFilterSettings($filter_settings)
  {
    $this->header_list['filters'] = $filter_settings;

    return $this;
  }
  
  /**
   * addFilterSetting
   * Append a filter setting to the list of filter settings
   *
   * @param  string         $filter_name     - filter name
   * @param  string         $parameter_name  - parameter name
   * @param  string         $parameter_value - setting value
   * @return Mail                    the SendGrid\Mail object. 
   */
  public function addFilterSetting($filter_name, $parameter_name, $parameter_value)
  {
    $this->header_list['filters'][$filter_name]['settings'][$parameter_name] = $parameter_value;

    return $this;
  }
  
  /**
   * getHeaders
   * return the list of headers
   *
   * @return array
   */
  public function getHeaders()
  {
    return $this->header_list;
  }

  /**
   * getHeaders
   * return the list of headers
   *
   * @return array
   */
  public function getHeadersJson()
  {
    if (count($this->getHeaders()) <= 0)
    {
      return "{}";
    }

    return json_encode($this->getHeaders(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
  }
  
  /**
   * setHeaders
   * Sets the list headers
   * destroys previous header data
   *
   * @param $key_value_pairs array
   * @return Mail
   */
  public function setHeaders($key_value_pairs)
  {
    $this->header_list = $key_value_pairs;

    return $this;
  }
    
  /**
   * addHeaders
   * append the header to the list of headers
   *
   * @param $key String
   * @param $value String
   * @return Mail
   */
  public function addHeader($key, $value)
  {
    $this->header_list[$key] = $value;

    return $this;
  }
  
  /**
   * removeHeaders
   * remove a header key
   *
   * @param $key String
   * @return Mail
   */
  public function removeHeader($key)
  {
    unset($this->header_list[$key]);

    return $this;
  }

  /**
   * useHeaders
   * Checks to see whether or not we can or should you headers. In most cases,
   * we prefer to send our recipients through the headers, but in some cases,
   * we actually don't want to. However, there are certain circumstances in 
   * which we have to.
   */
  public function useHeaders()
  {
    return !($this->_preferNotToUseHeaders() && !$this->_isHeadersRequired());
  }

  public function setRecipientsInHeader($preference)
  {
    $this->use_headers = $preference;

    return $this;
  }

  /**
   * isHeaderRequired
   * determines whether or not we need to force recipients through the smtpapi headers
   * @return boolean
   *
   */
  protected function _isHeadersRequired()
  {
    if(count($this->getAttachments()) > 0 || $this->use_headers )
    {
      return true;
    }
    return false;
  }

  /**
   * _preferNotToUseHeaders
   * There are certain cases in which headers are not a preferred choice
   * to send email, as it limits some basic email functionality. Here, we
   * check for any of those rules, and add them in to decide whether or 
   * not to use headers
   *
   * @return boolean
   */
  protected function _preferNotToUseHeaders()
  {
    if (count($this->getBccs()) > 0 || count($this->getCcs()) > 0)
    {
      return true;
    }
    if ($this->use_headers !== null && !$this->use_headers)
    {
      return true;
    }
    
    return false;
  }

}
