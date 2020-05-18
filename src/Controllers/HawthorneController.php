<?php

namespace flexiPIM\Hawthorne\Controllers;

use App\Repositories\Attributes\AttributeRepository;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Channels\ChannelRepository;
use App\Repositories\Extension\PimExtensionRepository;
use App\Repositories\Families\FamilyRepository;
use flexiPIM\Hawthorne\Repositories\HawthorneAttribute\HawthorneAttributeRepository;
use flexiPIM\Hawthorne\Repositories\LogRepository\LogRepository;
use flexiPIM\Hawthorne\API\HawthorneAttributes;
use flexiPIM\Hawthorne\Requests\HawthorneConfigurationRequest;
use flexiPIM\Hawthorne\Jobs\ProductSyncTrigger;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use flexiPIM\Hawthorne\Repositories\Configuration\HawthorneConfigurationRepository;

class HawthorneController extends Controller
{
    /**
     * @var HawthorneConfigurationRepository
     */
    private $configRepo;
    /**
     * @var ChannelRepository
     */
    public $channelRepository;
    /**
     * @var CategoryRepository
     */
    public $categoryRepository;
    /**
     * @var FamilyRepository
     */
    public $familyRepository;
    /**
     * @var HawthorneAttributeRepository
     */
    public $hawthorneAttributeRepo;
    /**
     * @var LogRepository
     */
    public $logRepo;
    /**
     * @var AttributeRepository
     */
    public $attributeRepository;
    /**
     * @var PimExtensionRepository
     */
    public $pimExtensionRepo;

    /**
     * HawthorneController constructor.
     * @param HawthorneConfigurationRepository $configRepo
     * @param ChannelRepository $channelRepository
     * @param CategoryRepository $categoryRepository
     * @param FamilyRepository $familyRepository
     * @param HawthorneAttributeRepository $hawthorneAttributeRepo
     * @param LogRepository $logRepo
     * @param PimExtensionRepository $pimExtensionRepo
     * @param AttributeRepository $attributeRepository
     */
    public function __construct
    (
        HawthorneConfigurationRepository $configRepo,
        ChannelRepository $channelRepository,
        CategoryRepository $categoryRepository,
        FamilyRepository $familyRepository,
        HawthorneAttributeRepository $hawthorneAttributeRepo,
        LogRepository $logRepo,
        PimExtensionRepository $pimExtensionRepo,
        AttributeRepository $attributeRepository
    )
    {
        $this->configRepo = $configRepo;
        $this->channelRepository = $channelRepository;
        $this->categoryRepository = $categoryRepository;
        $this->familyRepository = $familyRepository;
        $this->hawthorneAttributeRepo = $hawthorneAttributeRepo;
        $this->logRepo = $logRepo;
        $this->pimExtensionRepo = $pimExtensionRepo;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Function To Generate the Index Page
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @author KMG
     */
    public function index(Request $request)
    {
        /**
         * Place To get the Hawthorne Extension Details
         */
        $appURI = explode('/',$request->route()->uri);
        $extensionData = $this->pimExtensionRepo->getExtensionBySlug($appURI[1]);
        /**
         * Place to get the Hawthorne - FlexiPIM Mapped Attribute Count
         */
        $mappedAttributeCount = $this->hawthorneAttributeRepo->selectMappedAttribute()->count();

        /**
         * To get the Hawthorne Configuration Details
         */
        $configData = $this->configRepo->select()->first();

        $hawthorneAttribute = $this->hawthorneAttributeRepo->select()->count();

        return view('Hawthorne::index',compact('hawthorneAttribute','mappedAttributeCount','extensionData','configData'));
    }

    /**
     * Function To Initiate the Configuration View Page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @author KMG
     */
    public function viewConfig()
    {
        /**
         * To Select the Configuration Data From the HydroFarm Configuration Table
         */
        $config_data = $this->configRepo->select()->first();
        /**
         * To get the Cron Time Option From the Configuration Files.
         */
        $cronOption = config('constants.cron_option');
        /**
         * To Select the Available Channels List In flexiPIM
         */
        $channelList = $this->channelRepository->getChannelList()->pluck('title','id')->toArray();
        $channelList[0] = 'Select Channel';
        ksort($channelList);
        /**
         * To Get the Available Category List In flexiPIM.
         */
        $categoryList = $this->categoryRepository->getCategoryList()->pluck('title','id')->toArray();
        /**
         * TO Get the Available Family List in FlexiPIM
         */
        $familyList = $this->familyRepository->getFamiliesList()->pluck('title','id')->toArray();
        $familyList[0] = 'Select Family';
        ksort($familyList);

        return view('Hawthorne::configuration',compact('config_data','cronOption','channelList','categoryList','familyList'));
    }

    /**
     * Function To Store the Hawthorne Configuration
     *
     * @param HawthorneConfigurationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @author KMG
     */
    public function storeConfig(HawthorneConfigurationRequest $request)
    {
        /**
         * To Format the Insert Data For
         */
        $actionData = [
            'access_url' => $request->access_url,
            'client_key' => $request->client_key,
            'secret_key' => $request->secret_key,
            'products_access_url' => $request->products_access_url,
            'cron_time' => $request->cron_time,
            'channel_id' => $request->channel_id,
            'category_id' => $request->category_id,
            'family_id' => $request->family_id
        ];

        /**
         * To check the The Configuration Already Done Or Not
         * If, Done Just Update the Record else Need to insert it.
         */
        if(isset($this->configRepo->select()->first()->id)){
            /**
             * To Update the updated_by and updated_at fields
             */
            $actionData['updated_by'] = auth()->user()->id;
            $actionData['updated_at'] = Date('Y-m-d h:i:s');
            $this->configRepo->update($actionData,['id' => $this->configRepo->select()->first()->id]);
        }else{
            /**
             * To Update the created_by and created_at fields
             */
            $actionData['created_by'] = auth()->user()->id;
            $actionData['created_at'] = Date('Y-m-d h:i:s');
            $this->configRepo->save($actionData);
        }
        /**
         * To Redirect the configuration view page with message
         */
        return redirect()->route('hawthorne.config')->with(['response' => ['status' => true,'message' => 'Configuration Updated Successfully', 'alert' => 'success','heading' => 'Done']]);
    }

    /**
     * Function To Get the FlexiPIM And Hawthorne Attribute For Mapping Process
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @author KMG
     */
    public function getAttributeMapping()
    {
        /**
         * To load the HydroFarm Configuration Data
         */
        $configData = $this->configRepo->select()->first();
        if(isset($configData)){
            /**
             * To get the Attribute Data From the FlexiPIM
             */
            $hawthorneAttribute = $this->hawthorneAttributeRepo->select([])->pluck('attribute_code','id')->toArray();
            $hawthorneAttribute[0] = 'Select Attribute';
            ksort($hawthorneAttribute);

            $flexiPIMAttribute = $this->attributeRepository->getAttribute([])->pluck('title','id')->toArray();
            /**
             * Place to get the currently mapped attribute
             */
            $mappedAttribute = $this->hawthorneAttributeRepo->selectMappedAttribute()->pluck('hawthorne_attribute','pim_attribute')->toArray();

            return view('Hawthorne::mapping',compact('mappedAttribute','flexiPIMAttribute','configData','hawthorneAttribute'));

            throw new Exception();
        } else {
            return redirect()->route('hawthorne.config')->with(['response' => ['status' => false,'message' => 'Invalid Hawthorne Configurations', 'alert' => 'failed','heading' => 'Warning']]);
        }
    }

    /**
     * Function To Stored the Mapped Attributes
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @author KMG
     */
    public function storeAttribute(Request $request)
    {
        $mapInsertData = [];
        /**
         * Condition To check, SKU Attribute is not empty
         * If Empty throw the Error Message to client
         * else proceed the further process in flexiPIM
         */
        if($request->input('mapped_attribute')[2] > 0){
            foreach($request->input('mapped_attribute') as $pim_attribute => $hawthorne_attribute){
                /**
                 * Condition To Remove Un-Mapped Attribute in Files
                 */
                if($hawthorne_attribute > 0){
                    $mapInsertData[] = array(
                        'pim_attribute' => $pim_attribute,
                        'hawthorne_attribute' => $hawthorne_attribute
                    );
                }
            }
            $this->hawthorneAttributeRepo->destroyAttributeMapping();
            $this->hawthorneAttributeRepo->insertAttributeMapping($mapInsertData);
        }else{
            return redirect()->route('hawthorne.mapping')->with(['response' => ['status' => true,'message' => 'SKU field is required.', 'alert' => 'error','heading' => 'Failed']]);
        }

        return redirect()->route('hawthorne.mapping')->with(['response' => ['status' => true,'message' => 'Attribute Mapped Successfully', 'alert' => 'success','heading' => 'Done']]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getLog()
    {
        $logList = $this->logRepo->selectLog();
        return view('Hawthorne::log',compact('logList'));
    }

    /**
     * Function To Sync the HydroFarm Attribute Into FlexiPIM.
     *
     * @return mixed
     * @author KMG
     */
    public function syncAttribute()
    {
        /**
         * Place the Call the HydroFarm API And Sync
         * Attribute Data Into the FlexiPIM
         */
        $hawthorneAttributeAPI = new HawthorneAttributes();
        $result = $hawthorneAttributeAPI->getProductAttributeData();
        if($result['status']){
            $this->configRepo->update(['last_sync_date' => null]);
            return redirect()->route('hawthorne.mapping')->with(['response' => ['status' => true,'message' => 'Attribute Sync Completed Successfully', 'alert' => 'success','heading' => 'Done']]);
        }else{
            return redirect()->route('hawthorne.mapping')->with(['response' => ['status' => false,'message' => 'Something went wrong, please contact the flexiPIM Support Team', 'alert' => 'error','heading' => 'Failed']]);
        }
    }

    /**
     * Function To Sync the Product Data
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @author KMG
     */
    public function productSync(Request $request)
    {
        $configData = $this->configRepo->select()->first();

        if(isset($configData)){
            if($this->logRepo->activeLog()->count() == 0){
                $userId = auth()->user()->id;
                ProductSyncTrigger::dispatch($userId)
                    ->delay(now()->addSeconds(5));
            }else{
                return redirect()->back()->with(['response' => ['status' => true,'message' => 'Please Wait, Sync Already In-Progress', 'alert' => 'info','heading' => 'Info']]);
            }
        }else{
            return redirect()->route('hydro.config')->with(['response' => ['status' => false,'message' => 'Invalid Hawthorne Configurations', 'alert' => 'failed','heading' => 'Warning']]);
        }
        return redirect()->back()->with(['response' => ['status' => true,'message' => 'Product Sync Initiated Successfully', 'alert' => 'success','heading' => 'Done']]);
    }
}
