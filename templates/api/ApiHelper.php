<?php namespace ProcessWire;

class ApiHelper {
  const AUTH = false;

  public static function noEndPoint() {
    return 'No Endpoint specified!';
  }

  // Check for required parameter "message" and sanitize with PW Sanitizer
  public static function checkAndSanitizeRequiredParameters($params, $options) {
    foreach ($options as $option) {
      // Split param: Format is name|sanitizer
      $name = explode('|', $option)[0];

      // Check if Param exists
      if (!isset($params->$name)) throw new \Exception("Required parameter: '$option' missing!", 400);

      $sanitizer = explode('|', $option);

      // Sanitize Data
      // If no sanitizer is defined, use the text sanitizer as default
      if (!isset($sanitizer[1])) $sanitizer = 'text';
      else $sanitizer = $sanitizer[1];

      if(!method_exists(wire('sanitizer'), $sanitizer)) throw new \Exception("Sanitizer: '$sanitizer' ist no valid sanitizer", 400);

      $params->$name = wire('sanitizer')->$sanitizer($params->$name);
    }

    return $params;
  }

  public static function baseUrl() {
    // $site->urls->httpRoot
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";
  }

  public static function pagesToJSON(PageArray $pages) {
    $a = array();
    foreach($pages as $page) {
      if (!$page->isHidden()) {
        $a[] = self::pageToStructure($page);
      }
    }

    return $a;
  }

  private static function pageToStructure(Page $page) {
    $outputFormatting = $page->outputFormatting;
    $page->setOutputFormatting(false);

    $data = array(
      'id' => $page->id,
      'title' => self::getFieldValue($page, $page->template->fieldgroup->title),
      'parent_id' => $page->parent_id,
      'name' => $page->name,
      'path' => $page->path,
      'template' => $page->template->name
    );

    if (isset($page->link_placement)) {
      $data['link_placement'] = $page->link_placement->title;
    }

    $page->setOutputFormatting($outputFormatting);

    return $data;
  }

  public static function pageToArray(Page $page) {
    $outputFormatting = $page->outputFormatting;
    $page->setOutputFormatting(false);

    $data = array(
      'name' => $page->name,
      'template' => $page->template->name,
      'type' => $page->type,
      'status' => $page->status,
      'sort' => $page->sort,
      'data' => array()
      //'title' => $page->title,
      //'id' => $page->id,
      //'parent_id' => $page->parent_id,
      //'templates_id' => $page->templates_id,
      //'parent' => $page->parent->path,
      //'sortfield' => $page->sortfield,
      //'numChildren' => $page->numChildren,
    );

    $data = array_filter($data, function($val) {
      return !is_null($val);
    });

    foreach($page->template->fieldgroup as $field) {
      if($field->type instanceof FieldtypeFieldsetOpen) continue;

      $value = self::getFieldValue($page, $field);
      if ((isset($value) && !empty($value)) || substr($field->name, 0, 4) === 'form') {
        $data['data'][$field->name] = $value;
      }
    }

    $page->setOutputFormatting($outputFormatting);

    return $data;
  }

  private static function getFieldValue(Page $page, Field $field) {
    $user = wire('user');

    if($field->type instanceof FieldtypeRepeaterMatrix ||
      $field->type instanceof FieldtypeRepeater) {
      $pageArray = $page->get($field->name);

      $value = array();
      if (isset($pageArray)) {
        $repItems = array();
        foreach ($pageArray as $item) {
          $repItem = ApiHelper::pageToArray($item);
          array_push($repItems, $repItem);
        }
        $value['data'] = $repItems;
      }

      return $value;
    } elseif ($field->type instanceof FieldtypeImage ||
      $field->type instanceof FieldtypeFile) {
      $files = $page->get($field->name);
      $value = array();

      if (isset($files)) {
        foreach($files as $file) {
          $res = array(
            'url' => $file->url,
            'basename' => $file->basename,
            'description' => $file->description
          );
          array_push($value, $res);
        }
      }
      return $value;
    } else {
      $value = $page->get($field->name);

      // filter array with empty entries
      if (is_array($value)) {
        $value = array_filter($value, function($val) {
          return !is_null($val);
        });
        $value = empty($value) ? null : $value;
      }

      $fieldcontext = $page->template->fieldgroup->getFieldContext($field);
      $res = array(
        'type' => $field->type->name,
        //'title' => isset($fieldcontext->title) ? $fieldcontext->title : $field->title,
        'label' => isset($fieldcontext->label) ? $fieldcontext->label : $field->label,
        'required' => isset($fieldcontext->required) ? $fieldcontext->required : $field->required,
        'notes' => isset($fieldcontext->notes) ? $fieldcontext->notes : $field->notes,
        'description' => isset($fieldcontext->description) ? $fieldcontext->description : $field->description,
        'columnWidth' => isset($fieldcontext->columnWidth) ? $fieldcontext->columnWidth : $field->columnWidth,
        'minlength' => isset($fieldcontext->minlength) ? $fieldcontext->minlength : $field->minlength,
        'maxlength' => isset($fieldcontext->maxlength) ? $fieldcontext->maxlength : $field->maxlength,
        'size' => isset($fieldcontext->size) ? $fieldcontext->size : $field->size,
        'tags' => isset($fieldcontext->tags) ? $fieldcontext->tags : $field->tags,
        'placeholder' => isset($fieldcontext->placeholder) ? $fieldcontext->placeholder : $field->placeholder,
        'data' => method_exists($value, 'getLanguageValue') ? $value->getLanguageValue($user->language) : $value
      );

      if($field->type instanceof FieldtypeOptions) {
        $options = array();
        $all_options = $field->type->getOptions($field);
        foreach($all_options as $option) {
          $opt = array(
            'id' => $option->id,
            'title' => $option->title,
            'value' => $option->value,
          );
          array_push($options, $opt);
        }
        $res['options'] = $options;
        $res['data'] = !is_null($value) && !is_null($value->title) ? $value->title : '';
      }

      if($field->type instanceof FieldtypeOptions ||
        $field->type instanceof FieldtypeFormButton) {
        $res['inputfieldClass'] = $field->inputfieldClass;
      }

      $res = array_filter($res, function($val) {
        return !is_null($val);
      });

      return array_key_exists('data', $res) ? $res : array();
    }
  }
}
