<?php

namespace App\Validators;

use App\Libraries\Common\IAttribute;
use Attribute;
use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

#[Attribute]
class Validator implements IAttribute
{
    protected array $validator;

    public function __construct($validator, $method)
    {
        $this->validator = [$validator, $method];
    }

    public function run($args)
    {
        call_user_func_array($this->validator, [$args]);
    }

    /**
     * @throws Exception
     */
    public static function validate($request, $validation)
    {
        $validator = ValidatorFacade::make($request->all(), $validation);
        if ($validator->fails()) {
            throw \Illuminate\Validation\ValidationException::withMessages($validator->messages()->get('*'));
        }
    }
}
