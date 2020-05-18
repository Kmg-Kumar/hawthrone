<?php


namespace flexiPIM\Hawthorne\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class HawthorneConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request)
    {
        return [
            'access_url' => 'required|url',
            'client_key' => 'required',
            'secret_key' => 'required',
            'products_access_url' => 'required|url',
            'channel_id' => 'required|numeric',
            'category_id' => 'required|numeric',
            'family_id' => 'required|numeric'
        ];
    }
}
