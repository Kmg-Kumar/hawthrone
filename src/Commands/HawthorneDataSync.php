<?php

namespace flexiPIM\Hawthorne\Commands;

use App\PimAttributeOptions;
use App\Repositories\Assets\AssetsRepository;
use App\Repositories\Attributes\AttributeRepository;
use App\Repositories\Families\FamilyRepository;
use flexiPIM\Hawthorne\API\HawthorneProducts;
use flexiPIM\Hawthorne\Helpers\HawthorneHelper;
use flexiPIM\Hawthorne\Repositories\HawthorneAttribute\HawthorneAttributeRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Exception;
use Storage;
use Validator;
use Log;
use DB;

class HawthorneDataSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hawthorne:sync {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hawthorne Data Provider Sync';

    /**
     * @var HawthorneProducts
     */
    protected $hawthorneProductAPI;

    /**
     * @var HawthorneAttributeRepository
     */
    public $attributeRepository;

    /**
     * @var FamilyRepository
     */
    public $familyRepository;

    /**
     * @var AttributeRepository
     */
    public $pimAttributeRepository;

    /**
     * @var AssetsRepository
     */
    public $assetsRepository;

    /**
     * @var HawthorneHelper
     */
    public $hawthorneHelper;

    public $user_id;

    public $logId;

    /**
     * HawthorneDataSync constructor.
     * @param HawthorneAttributeRepository $attributeRepository
     * @param FamilyRepository $familyRepository
     * @param AttributeRepository $pimAttributeRepository
     * @param AssetsRepository $assetsRepository
     * @param HawthorneHelper $hawthorneHelper
     */
    public function __construct(
        HawthorneAttributeRepository $attributeRepository,
        FamilyRepository $familyRepository,
        AttributeRepository $pimAttributeRepository,
        AssetsRepository $assetsRepository,
        HawthorneHelper $hawthorneHelper
    )
    {
        $this->hawthorneProductAPI = new HawthorneProducts();
        $this->attributeRepository = $attributeRepository;
        $this->familyRepository = $familyRepository;
        $this->pimAttributeRepository = $pimAttributeRepository;
        $this->assetsRepository = $assetsRepository;
        $this->hawthorneHelper = $hawthorneHelper;
        $this->user_id = empty($this->getArguments('user')) ? 1 : $this->argument('user');
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @throws Exception
     * @return mixed
     */
    public function handle()
    {
        $this->initiateCronLog();
        $productData = $this->hawthorneProductAPI->getProductData();
        if($productData['status']){
            foreach($productData['data'] as $keys => $attributeKey)
            {
                $attributeKey = (array) $attributeKey;

                /**
                 * Prepare the required attribute flexiPIM Product insertion
                 */
                $filterProductData = $this->requiredAttributeFilter($attributeKey);
                $isSkuExist = $this->hawthorneHelper->isSku2Present($attributeKey['Id']);
                /**
                 * To Generate the Case Price
                 */
                if(!empty($attributeKey['EachPrice']) && $attributeKey['SoldInQuantitiesOf'] > 1){
                    $eachPriceArray = $this->attributeRepository->selectMappedAttributeWithCode(['hawthorne_attribute.attribute_code' => 'EachPrice'])->pluck('pim_attribute')->toArray();
                    foreach($eachPriceArray as $priceItem) {
                        $filterProductData[$priceItem] = $attributeKey['SoldInQuantitiesOf'] * $attributeKey['EachPrice'];
                    }
                }
                /**
                 * To Update CaseMSRP
                 */
                if(!empty($attributeKey['EachMsrp']) && $attributeKey['SoldInQuantitiesOf'] > 1){
                    $eachPriceArray = $this->attributeRepository->selectMappedAttributeWithCode(['hawthorne_attribute.attribute_code' => 'EachMsrp'])->pluck('pim_attribute')->toArray();
                    foreach($eachPriceArray as $priceItem) {
                        $filterProductData[$priceItem] = $attributeKey['SoldInQuantitiesOf'] * $attributeKey['EachMsrp'];
                    }
                }
                /**
                 * To Update Case Weight
                 */
                if(!empty($attributeKey['EachWeight']) && $attributeKey['SoldInQuantitiesOf'] > 1){
                    $eachPriceArray = $this->attributeRepository->selectMappedAttributeWithCode(['hawthorne_attribute.attribute_code' => 'EachWeight'])->pluck('pim_attribute')->toArray();
                    foreach($eachPriceArray as $priceItem) {
                        $filterProductData[$priceItem] = $attributeKey['SoldInQuantitiesOf'] * $attributeKey['EachWeight'];
                    }
                }

                /**
                 * Place to check the product exist or not
                 * If, Product exist it initiate the product update
                 * else to Insert the product to flexiPIM
                 */
                if(isset($isSkuExist)){
                    Log::channel('cron_log')->info('Product Update');
                    Log::channel('cron_log')->info('SKU - '.$isSkuExist->code);
                    try{
                        $productUpdateStatus = $this->productUpdate($filterProductData,$isSkuExist);
                        if($productUpdateStatus){
                            Log::channel('cron_log')->info('Product Update Completed successfully');
                        }else{
                            Log::channel('cron_log')->info('Product Update failed');
                        }
                        throw new Exception();
                    }catch (\Exception $e){
                        Log::channel('cron_log')->info($e->getMessage());
                    }
                }else {
                    Log::channel('cron_log')->info('Product Insert Started');
                    try {
                        if (isSkuActive($attributeKey['Id']) == null) {
                            $productInsertStatus = $this->productInsert($filterProductData);
                            if ($productInsertStatus) {
                                Log::channel('cron_log')->info('Product Insert Completed Successfully');
                            } else {
                                Log::channel('cron_log')->info('Product Insert Failed');
                            }
                        } else {
                            Log::channel('cron_log')->info($attributeKey['Id']);
                            Log::channel('cron_log')->info('Product Base SKU Already Exist.');
                            Log::channel('cron_log')->info('Product Insert Failed');
                        }
                        throw new Exception();
                    } catch (\Exception $e) {
                        Log::channel('cron_log')->info($e->getMessage());
                    }
                }
            }
        }else{
            Log::channel('cron_log')->info($productData['message']);
        }

        $this->updateCronLog('success');
    }

    /**
     * Function To Create the Log File For Hawthorne Cron Run
     *
     * @return string
     * @author KMG
     */
    function initiateCronLog()
    {
        if(Schema::hasTable('hawthorne_configuration')){
            DB::table('hawthorne_configuration')
                ->where('id',$this->hawthorneProductAPI->hawthorneConfig->id)
                ->update(['last_sync_date' => Date('Y-m-d H:i:s')]);
        }

        if(Schema::hasTable('hawthorne_log')){
            $fileName = 'hawthorne_'.Date('Y_m_d_H_i_s').'.log';
            config(['logging.channels.cron_log.path' => storage_path('app/public/cron/hawthorne/'.$fileName)]);
            $this->logId = DB::table('hawthorne_log')->insertGetId([
                'file_name' => $fileName,
                'status' => 'processing',
                'created_by' => $this->user_id,
                'created_at' => Date('Y-m-d H:i:s')
            ]);

            return $fileName;
        }
    }

    /**
     * Function To Update the Cron Status
     *
     * @param string $status
     * @author KMG
     */
    public function updateCronLog($status = 'Success')
    {
        DB::table('hawthorne_log')
            ->where('id',$this->logId)
            ->update(['status' => $status]);
    }

    /**
     * Function To Filter the Required Attribute for FlexiPIM
     *
     * @param $actualProductData
     * @return mixed
     * @author KMG
     */
    public function requiredAttributeFilter($actualProductData = array())
    {
        $filterCode = $this->attributeRepository->selectMappedAttributeWithCode([])->pluck('attribute_code','pim_attribute')->toArray();

        $hydroFarmCodeShift  = [];
        foreach ($filterCode as $key => $value){
            if(array_key_exists($value,$actualProductData)){
                $hydroFarmCodeShift[$key] = html_entity_decode($actualProductData[$value]);
            }
        }

        return $hydroFarmCodeShift;
    }

    /**
     * Function to Create the Product Insert
     *
     * @param array $productData
     * @return bool
     * @author KMG
     */
    public function productInsert($productData = array())
    {
        if(isset($productData['sku_2'])){
            Log::channel('cron_log')->info($productData['sku_2']);

            /**
             * To Generate the Case Price
             */
            if(!empty($attributeKey['EachPrice']) && $attributeKey['SoldInQuantitiesOf'] > 1){
                $attributeKey['CasePrice'] = $attributeKey['SoldInQuantitiesOf'] * $attributeKey['EachPrice'];
            }

            /**
             * Place to insert the pim_product root table
             */
            $insertProductData = [
                'product_status_id' => 1,
                'code' => $productData['sku_2'],
                'status' => config('constants.status.active'),
                'percentage' => 0,
                'created_by' => $this->user_id,
                'modified_by' => $this->user_id,
                'created_at' => Date('Y-m-d H:i:s'),
                'updated_at' => Date('Y-m-d H:i:s')
            ];

            $productId = DB::table('pim_products')->insertGetId($insertProductData);

            /**
             * To Insert the family id for appropriate the product id
             */
            $insertProductFamily = [
                'product_id' => $productId,
                'group_id' => $this->hawthorneProductAPI->hawthorneConfig->family_id
            ];

            DB::table('pim_attribute_group_products')->insert($insertProductFamily);

            $insertCategoryData = [
                'product_id' => $productId,
                'category_id' => $this->hawthorneProductAPI->hawthorneConfig->category_id,
                'channel_id' => $this->hawthorneProductAPI->hawthorneConfig->channel_id
            ];

            DB::table('pim_category_products')->insert($insertCategoryData);

            $insertChannelData = [
                'product_id' => $productId,
                'channel_id' => $this->hawthorneProductAPI->hawthorneConfig->channel_id
            ];

            DB::table('pim_channel_products')->insert($insertChannelData);

            /**
             * To Get the Product Family Attribute List
             */
            $familyAttributeIds = $this->familyRepository->getFamilyWithAttribute($this->hawthorneProductAPI->hawthorneConfig->family_id)->pluck('attribute_id')->toArray();
            /**
             * To get the Default Product Attributes
             */
            $defaultAttributeId = config('constants.default_attribute_id');
            unset($defaultAttributeId['meta_description']);
            unset($defaultAttributeId['meta_keyword']);
            $productAttributeId = array_merge($familyAttributeIds,array_values($defaultAttributeId));

            /**
             * To get the Attribute Collection From the FlexiPIM
             */
            $pimAttributeCollection = $this->pimAttributeRepository->selectAttributeWithEntityTable('pim_attribute_fields.id',$productAttributeId)->get()->toArray();

            /**
             * Place To Process the Update Data One By One.
             */
            foreach($pimAttributeCollection as $attribute){

                /**
                 * Place To Find the Attribute Code Exist In Product Data
                 */
                if(array_key_exists($attribute->attribute_code,$productData)){

                    $insertData = array();
                    /**
                     * To Remove The SKU From the update process
                     */
                    if($attribute->attribute_code != 'sku'){
                        /**
                         * To Build the Validation Rule for given update Data
                         */
                        $validationRule = attribute_validation_builder($attribute);
                        /**
                         * To Build The Validation Message for given updated Data
                         */
                        $validationMessage = attribute_validation_message_bulider($attribute,['en_US']);
                        /**
                         * Place To Run the Validation For Updated HF Data
                         */
                        $dynamicValidator = Validator::make([$attribute->attribute_code => $productData[$attribute->attribute_code]], [$attribute->attribute_code => $validationRule], $validationMessage);
                        /**
                         * The Condition To check the Validation Pass Or Fail
                         * If, Condition Pass To Insert or Update Record
                         * if Fails, to print the error log in logger file
                         */
                        if ($dynamicValidator->fails()) {
                            Log::channel('cron_log')->info('Validation Status');
                            Log::channel('cron_log')->info($attribute->attribute_code);
                            Log::channel('cron_log')->info($dynamicValidator->messages()->getMessages());
                        }else{
                            /**
                             * Condition To check the given attribute is select or not
                             * if, condition is select to choose the values from the option
                             * table and update the record
                             */
                            if($attribute->input_type == 'select'){
                                /**
                                 * To check the option is array or string.
                                 */
                                if(is_array($productData[$attribute->attribute_code])){
                                    $optionArray = $productData[$attribute->attribute_code];
                                }else{
                                    $optionArray = explode(',', $productData[$attribute->attribute_code]);
                                }
                                $language_id = 1;
                                /**
                                 * To Prepare the insert data for select option insert
                                 */
                                foreach ($optionArray as $optionKey => $optionValue) {

                                    $insertData = array(
                                        'attribute_id' => $attribute->id,
                                        'product_id' =>  $productId,
                                        'language_id' => 1,
                                        'attribute_code' => '',
                                    );

                                    $attribute_select_option = PimAttributeOptions::where('option_value', $optionValue)
                                        ->where('language_id', $language_id)
                                        ->where('pim_attribute_field_id', '=', $attribute->id)
                                        ->first();
                                    /**
                                     * Place the check the option field is available or not
                                     * If select option available to insert the product values
                                     * else to insert the option and insert he product values
                                     */
                                    if (isset($attribute_select_option['id'])) {

                                        $insertData['product_attribute_values'] =  $attribute_select_option->id;

                                        $isRecAlreadyExist = DB::table($attribute->entity_table_name)
                                            ->where('language_id',1)
                                            ->where('product_id',$productId)
                                            ->where('attribute_id',$attribute->id)
                                            ->where('product_attribute_values',$attribute_select_option->id)->first();

                                        if(!isset($isRecAlreadyExist)){
                                            DB::table($attribute->entity_table_name)
                                                ->insert($insertData);
                                        }
                                    } else {
                                        $optionId = insertProductOption($attribute->id,$optionValue,1);
                                        $insertData['product_attribute_values'] =  $optionId;
                                        DB::table($attribute->entity_table_name)->insert($insertData);
                                    }
                                }
                            }else if(($attribute->pim_attribute_input_field_id == config('constants.attribute_type.image') || $attribute->pim_attribute_input_field_id == config('constants.attribute_type.file')) && isset($productData[$attribute->attribute_code])){
                                if(is_array($productData[$attribute->attribute_code])){
                                    $assetsUrls = $productData[$attribute->attribute_code];
                                }else{
                                    $assetsUrls = explode(',',$productData[$attribute->attribute_code]);
                                }

                                foreach($assetsUrls as $fileUrl){
                                    /**
                                     * To check the Image is Already Exist in flexiPIM
                                     * If is Present in flexiPIM Just Add the Name into the PIM
                                     */
                                    $assetsInfo = get_download($fileUrl, false, [], '', $attribute->pim_attribute_input_field_id == config('constants.attribute_type.image') ? 'public/product_assets/media/' : 'public/product_assets/documents/');
                                    $language_id = 1;
                                    if ($assetsInfo['status']) {
                                        $assets_entity['file_name'] = $assetsInfo['data']['name'];
                                        $assets_entity['file_extension'] = $assetsInfo['data']['format'];
                                        $assets_entity['file_type'] = $attribute->pim_attribute_input_field_id == config('constants.attribute_type.image') ? config('constants.file_type.media') : config('constants.file_type.document');
                                        $assets_entity['file_size'] = $assetsInfo['data']['size'];
                                        $assets_entity['width'] = isset($assetsInfo['data']['width']) ? $assetsInfo['data']['width'] : '';
                                        $assets_entity['height'] = isset($assetsInfo['data']['height']) ? $assetsInfo['data']['height'] : '';
                                        $assets_entity['created_by'] = $this->user_id;
                                        $assets_entity['status'] = config('constants.status.active');
                                        $assetsData = $this->assetsRepository->save($assets_entity,['pim_assets_directory_id' => 1]);

                                        $assetIntData['product_attribute_values'] = $assetsData[0]->pim_assets_id;
                                        $assetIntData['attribute_code'] = "Assets";
                                        $assetIntData['attribute_id'] = $attribute->id;
                                        $assetIntData['product_id'] = $productId;
                                        $assetIntData['language_id'] = $language_id;
                                        DB::table('pim_product_int_entities')->insertGetID($assetIntData);
                                    }
                                }
                            }else{
                                $insertData = array(
                                    'attribute_id' => $attribute->id,
                                    'product_id' =>  $productId,
                                    'language_id' => 1,
                                    'attribute_code' => '',
                                    'product_attribute_values' => $productData[$attribute->attribute_code]
                                );
                                if($attribute->input_field_name == 'radio'){
                                    if((string) $productData[$attribute->attribute_code] != null && (string) $productData[$attribute->attribute_code] != ''){
                                        $insertData['product_attribute_values'] = $productData[$attribute->attribute_code] == 0 ? 1 : 2;
                                    }else{
                                        $insertData['product_attribute_values'] = '';
                                    }
                                }
                                if($attribute->input_field_name == 'metrics'){
                                    $customData = json_decode($attribute->custom_fields);
                                    $insertData['attribute_code'] = isset($customData->metric_option) ? $customData->metric_option : '';
                                }
                                if($attribute->entity_table_name == 'pim_product_decimal_entities'){
                                    $insertData['attribute_code'] = 'USD';
                                }
                                if($attribute->entity_table_name == 'pim_product_int_entities'){
                                    $insertData['sort_order'] = 1;
                                }

                                DB::table($attribute->entity_table_name)
                                    ->insert($insertData);
                            }
                            /**
                             * Place to product updated at time
                             */
                            DB::table('pim_products')->where('id',$productId)->update(['created_at' => Date('Y-m-d H:i:s')]);

                        }
                    }
                }
            }
            /**
             * Place To Update Product Completeness Percentage
             */
            $completenessStatus = productCompletion($productId,$this->hawthorneProductAPI->hawthorneConfig->family_id,1);
            if($completenessStatus['status']){
                DB::table('pim_products')->where('id',$productId)->update(['percentage' => $completenessStatus['completeness']]);
            }
        }else{
            Log::channel('cron_log')->info('Product SKU is Mandatory.');
            return false;
        }

        return true;
    }

    /**
     * Function To Update the Product Data
     *
     * @param $productData
     * @param $isSkuExist
     * @return bool
     * @author KMG
     */
    public function productUpdate($productData,$isSkuExist)
    {
        $productFamilyId = $this->familyRepository->getProductFamilyById($isSkuExist->id);
        /**
         * To Get the Product Family Attribute List
         */
        $familyAttributeIds = $this->familyRepository->getFamilyWithAttribute($productFamilyId)->pluck('attribute_id')->toArray();
        /**
         * To get the Default Product Attributes
         */
        $defaultAttributeId = config('constants.default_attribute_id');
        unset($defaultAttributeId['meta_description']);
        unset($defaultAttributeId['meta_keyword']);
        $productAttributeId = array_merge($familyAttributeIds,array_values($defaultAttributeId));
        /**
         * To get the Attribute Collection From the FlexiPIM
         */
        $pimAttributeCollection = $this->pimAttributeRepository->selectAttributeWithEntityTable('pim_attribute_fields.id',$productAttributeId)->get()->toArray();

        /**
         * Place To Process the Update Data One By One.
         */
        foreach($pimAttributeCollection as $attribute){

            /**
             * Place To Find the Attribute Code Exist In Product Data
             */
            if(array_key_exists($attribute->attribute_code,$productData)){

                $updateData = array();
                /**
                 * To Remove The SKU From the update process
                 */
                if($attribute->attribute_code != 'sku' && $attribute->attribute_code != 'sku_2'){
                    /**
                     * To Build the Validation Rule for given update Data
                     */
                    $validationRule = attribute_validation_builder($attribute);
                    /**
                     * To Build The Validation Message for given updated Data
                     */
                    $validationMessage = attribute_validation_message_bulider($attribute,['en_US']);
                    /**
                     * Place To Run the Validation For Updated HF Data
                     */
                    $dynamicValidator = Validator::make([$attribute->attribute_code => $productData[$attribute->attribute_code]], [$attribute->attribute_code => $validationRule], $validationMessage);
                    /**
                     * The Condition To check the Validation Pass Or Fail
                     * If, Condition Pass To Insert or Update Record
                     * if Fails, to print the error log in logger file
                     */
                    if ($dynamicValidator->fails()) {
                        Log::channel('cron_log')->info('Validation Status');
                        Log::channel('cron_log')->info($attribute->attribute_code);
                        Log::channel('cron_log')->info($dynamicValidator->messages()->getMessages());
                    }else{

                        /**
                         * Condition To check the given attribute is select or not
                         * if, condition is select to choose the values from the option
                         * table and update the record
                         */
                        if($attribute->input_type == 'select'){
                            /**
                             * To check the option is array or string.
                             */
                            if(is_array($productData[$attribute->attribute_code])){
                                $optionArray = $productData[$attribute->attribute_code];
                            }else{
                                $optionArray = explode(',', $productData[$attribute->attribute_code]);
                            }
                            $language_id = 1;
                            /**
                             * To Prepare the insert data for select option insert
                             */
                            foreach ($optionArray as $optionKey => $optionValue) {

                                $updateData = array(
                                    'attribute_id' => $attribute->id,
                                    'product_id' =>  $isSkuExist->id,
                                    'language_id' => 1,
                                    'attribute_code' => '',
                                );

                                $attribute_select_option = PimAttributeOptions::where('option_value', $optionValue)->where('language_id', $language_id)
                                    ->where('pim_attribute_field_id', '=', $attribute->id)->first();
                                /**
                                 * Place the check the option field is available or not
                                 * If select option available to insert the product values
                                 * else to insert the option and insert he product values
                                 */
                                if (isset($attribute_select_option['id'])) {

                                    $updateData['product_attribute_values'] =  $attribute_select_option->id;

                                    $isRecAlreadyExist = DB::table($attribute->entity_table_name)
                                        ->where('language_id',1)
                                        ->where('product_id',$isSkuExist->id)
                                        ->where('attribute_id',$attribute->id)
                                        ->where('product_attribute_values',$attribute_select_option->id)->first();

                                    if(!isset($isRecAlreadyExist)){
                                        DB::table($attribute->entity_table_name)
                                            ->insert($updateData);
                                    }else{
                                        DB::table($attribute->entity_table_name)
                                            ->where('language_id',1)
                                            ->where('product_id',$isSkuExist->id)
                                            ->where('attribute_id',$attribute->id)
                                            ->update($updateData);
                                    }
                                } else {

                                    $optionId = insertProductOption($attribute->id,$optionValue,1);

                                    $updateData['product_attribute_values'] =  $optionId;

                                    DB::table($attribute->entity_table_name)->insert($updateData);
                                }
                            }
                        }else if(($attribute->pim_attribute_input_field_id == config('constants.attribute_type.image') || $attribute->pim_attribute_input_field_id == config('constants.attribute_type.file')) && isset($productData[$attribute->attribute_code])){

                            if(is_array($productData[$attribute->attribute_code])){
                                $assetsUrls = $productData[$attribute->attribute_code];
                            }else{
                                $assetsUrls = explode(',',$productData[$attribute->attribute_code]);
                            }
                            /**
                             * To Delete the Default Image Before Insert the Data
                             */
                            $existingAssets = DB::table('pim_product_int_entities')
                                ->join('pim_assets','pim_product_int_entities.product_attribute_values','=','pim_assets.id')
                                ->where('attribute_id',$attribute->id)
                                ->where('product_id',$isSkuExist->id)
                                ->get()->toArray();

                            $fileOriginalName = [];
                            foreach ($existingAssets as $assetsId => $filename){
                                $fileNameSplit = explode('_',$filename->file_name);
                                unset($fileNameSplit[0]);
                                $fileOriginalName[$assetsId] = implode('_',$fileNameSplit);
                            }
                            /**
                             * To Delete the Default Image Before Insert the Data
                             */
                            DB::table('pim_product_int_entities')
                                ->where('attribute_id',$attribute->id)
                                ->where('product_id',$isSkuExist->id)->delete();

                            $deletedAssetsIds = [];
                            foreach($existingAssets as $assetsId => $deletedAsset){
                                $deletedAssetsIds[] = $deletedAsset->id;
                                if ($deletedAsset->file_type == 1) {
                                    Storage::delete('public/product_assets/media/' . $deletedAsset->file_name);
                                    Storage::delete('public/product_assets/thumbnail/' . $deletedAsset->file_name);
                                } else {
                                    Storage::delete('public/product_assets/documents/' . $deletedAsset->file_name);
                                }
                            }
                            DB::table('pim_assets')->whereIn('id',$deletedAssetsIds)->update(['status' => config('constants.status.delete')]);

                            foreach($assetsUrls as $fileUrl){
                                /**
                                 * To check the Image is Already Exist in flexiPIM
                                 * If is Present in flexiPIM Just Add the Name into the PIM
                                 */
                                $assetsInfo = get_download($fileUrl, false, [], '', $attribute->pim_attribute_input_field_id == config('constants.attribute_type.image') ? 'public/product_assets/media/' : 'public/product_assets/documents/');
                                $language_id = 1;
                                if ($assetsInfo['status']) {
                                    $assets_entity['file_name'] = $assetsInfo['data']['name'];
                                    $assets_entity['file_extension'] = $assetsInfo['data']['format'];
                                    $assets_entity['file_type'] = $attribute->pim_attribute_input_field_id == config('constants.attribute_type.image') ? config('constants.file_type.media') : config('constants.file_type.document');
                                    $assets_entity['file_size'] = $assetsInfo['data']['size'];
                                    $assets_entity['width'] = isset($assetsInfo['data']['width']) ? $assetsInfo['data']['width'] : '';
                                    $assets_entity['height'] = isset($assetsInfo['data']['height']) ? $assetsInfo['data']['height'] : '';
                                    $assets_entity['created_by'] = $this->user_id;
                                    $assets_entity['status'] = config('constants.status.active');
                                    if(in_array($assetsInfo['data']['original_name'],$fileOriginalName)){
                                        $assets_entity['file_name'] = $existingAssets[array_search($assetsInfo['data']['original_name'],$fileOriginalName)]->file_name;
                                        $srcFile = $attribute->pim_attribute_input_field_id == config('constants.attribute_type.image') ? 'public/product_assets/media/'.$assetsInfo['data']['name'] : 'public/product_assets/documents/'.$assetsInfo['data']['name'];
                                        $trgFile = $attribute->pim_attribute_input_field_id == config('constants.attribute_type.image') ? 'public/product_assets/media/'.$existingAssets[array_search($assetsInfo['data']['original_name'],$fileOriginalName)]->file_name : 'public/product_assets/documents/'.$existingAssets[array_search($assetsInfo['data']['original_name'],$fileOriginalName)]->file_name;
                                        Storage::move($srcFile,$trgFile);
                                        $assets_entity['updated_at'] = Date('Y-m-d H:i:s');
                                        $assets_entity['status'] = config('constants.status.active');
                                        $this->assetsRepository->update($assets_entity,['id' => $existingAssets[array_search($assetsInfo['data']['original_name'],$fileOriginalName)]->id]);
                                        $assetsId = $existingAssets[array_search($assetsInfo['data']['original_name'],$fileOriginalName)]->id;
                                    }else{
                                        $assets_entity['file_name'] = $assetsInfo['data']['name'];
                                        $assetsData = $this->assetsRepository->save($assets_entity,['pim_assets_directory_id' => 1]);
                                        $assetsId = $assetsData[0]->pim_assets_id;
                                    }

                                    $assetIntData['product_attribute_values'] = $assetsId;
                                    $assetIntData['attribute_code'] = "Assets";
                                    $assetIntData['attribute_id'] = $attribute->id;
                                    $assetIntData['product_id'] = $isSkuExist->id;
                                    $assetIntData['language_id'] = $language_id;
                                    DB::table('pim_product_int_entities')->insertGetID($assetIntData);
                                }
                            }
                        }else{
                            $updateData = array(
                                'attribute_id' => $attribute->id,
                                'product_id' =>  $isSkuExist->id,
                                'language_id' => 1,
                                'attribute_code' => '',
                                'product_attribute_values' => $productData[$attribute->attribute_code]
                            );
                            if($attribute->input_field_name == 'radio'){
                                if( ((string) $productData[$attribute->attribute_code] != null) && (string) $productData[$attribute->attribute_code] != ''){
                                    $updateData['product_attribute_values'] = $productData[$attribute->attribute_code] == 0 ? 1 : 2;
                                }else{
                                    $updateData['product_attribute_values'] = '';
                                }
                            }
                            if($attribute->input_field_name == 'metrics'){
                                $customData = json_decode($attribute->custom_fields);
                                $updateData['attribute_code'] = isset($customData->metric_option) ? $customData->metric_option : '';
                            }
                            if($attribute->entity_table_name == 'pim_product_decimal_entities'){
                                $updateData['attribute_code'] = 'USD';
                            }
                            if($attribute->entity_table_name == 'pim_product_int_entities'){
                                $updateData['sort_order'] = 1;
                            }

                            DB::table($attribute->entity_table_name)
                                ->updateOrInsert([
                                    'attribute_id' => $attribute->id,
                                    'product_id' => $isSkuExist->id,
                                    'language_id' => 1
                                ],$updateData);
                        }
                        /**
                         * Place to product updated at time
                         */
                        DB::table('pim_products')->where('id',$isSkuExist->id)->update(['updated_at' => Date('Y-m-d H:i:s')]);

                        $completenessStatus = productCompletion($isSkuExist->id,$productFamilyId,1);
                        if($completenessStatus['status']){
                            DB::table('pim_products')->where('id',$isSkuExist->id)->update(['percentage' => $completenessStatus['completeness']]);
                        }
                    }
                }
            }
        }

        return true;
    }
}
