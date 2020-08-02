<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Services\ExternalApiWrapper;


class ContactController extends Controller
{
    protected $externalApi;

    protected $contacts;

    public function __construct()
    {
        $this->externalApi = new ExternalApiWrapper();

    }

    public function index()
    {

        //Get User Info
        //$results = $externalApi->call('contacts', 'load', [605006,['FirstName','LastName']] );

        $user = Auth::user();

        $aff_info = $this->getAffInfo($user->api_id);
        if(isset($aff_info[0]))
        {
            $totals = $this->externalApi->call('affiliates','affRunningTotals',[[$aff_info[0]['Id']]]);
        $summary = $totals[0];

        $query = array('AffiliateId' => $aff_info[0]['Id']);
        $return = array('ContactId');
        $referrals = $this->externalApi->call('data', 'query', ['Referral',1000, 0, $query, $return, 'Id', false] );
        sleep(.5);

        //return var_dump($referrals);
        $referralIds = array();
        if(count($referrals)){
            foreach ($referrals as $key => $value) {
                array_push($referralIds, $value['ContactId']);
            }
            $queryData = ['Id' => $referralIds];
            //return var_dump($queryData);

            $selectedFields =['Id', 'FirstName', 'LastName', 'Phone1','Email','DateCreated', '_Status2', '_AppsPending', '_AppsApproved', '_FundingAmountApprovedsofar', 'Groups'];
            $contacts = $this->externalApi->call('data', 'query', ['Contact',1000, 0, $queryData, $selectedFields, 'Id', false] );
        }else{
            $contacts = array();
        }
        //return var_dump($referralIds);

        //return var_dump($contacts);

        //return var_dump($summary);
        }

        if(isset($contacts)){
            return view('home' , ['aff_info' => $aff_info, 'contacts' => $contacts, 'user' => $user, 'summary' =>  $summary, 'ids' => $referralIds] );
        }else{
            $contacts = array();
            $summary = array();
            $referralIds = array();

            return view('home' , ['aff_info' => $aff_info, 'contacts' => $contacts, 'user' => $user, 'summary' =>  $summary, 'ids' => $referralIds] );
        }


    }

    public function profile()
    {
            $user = Auth::user();

             $queryData = ['Id' => $user['api_id']];
            $selectedFields =['Id', 'FirstName', 'LastName', 'Phone1','Email'];
            $results = $this->externalApi->call('data', 'query', ['Contact',1, 0, $queryData, $selectedFields, 'Id', false] );
            $user['inf'] = $results[0];

            //var_dump($results);

            return view('profile', ['user' => $user]);
    }

    public function save(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();

        $save = User::find($user['id']);

        $name = $data['FirstName'] . " " . $data['LastName'];

        $save->name =  $name;
        $save->email = $data['Email'];

        $save->save();

        $contact = array('FirstName' => $data['FirstName'], 'LastName' => $data['LastName'], 'Email' => $data['Email'], 'Phone1' => $data['Phone1'] );
        $contact_id = $this->externalApi->call('contacts', 'update',[$user['api_id'], $contact] );

        //$this->externalApi->call('emails', 'optIn',[$data['Email'],'Added by one of our referral partners to recieve more info'] );

        return redirect('/profile//');

    }

    public function pass(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|min:6|confirmed'
        ]);

        $data = $request->all();
        $user = Auth::user();

        $save = User::find($user['id']);

        $save->password = bcrypt($data['password']);

        $save->save();

        $contact = array('Password' => $data['password']);
        $contact_id = $this->externalApi->call('contacts', 'update',[$user['api_id'], $contact] );

        return redirect('/profile//');
    }

    private function getAffInfo($api_id)
        {
                    //Get the users Aff info
        $queryData = ['ContactId' => $api_id];
        $selectedFields = ['Id', 'AffCode'];
            $results = $this->externalApi->call('data', 'query', ['Affiliate',1, 0, $queryData, $selectedFields, 'Id', false] );

            return $results;
        }

        private function getReferrals($comissions)
        {
            $specialData = array();

            foreach ($comissions as $comission => $value) {
                $queryData = ['Id' => $value['ContactId']];
                $selectedFields =['Id', 'FirstName', 'LastName', 'Phone1','Email','DateCreated', '_Status2', '_AppsPending', '_AppsApproved', '_FundingAmountApprovedsofar', 'Groups'];
                $results = $this->externalApi->call('data', 'query', ['Contact',1000, 0, $queryData, $selectedFields, 'Id', false] );
                if (isset($results[0]))
                {
                    $specialData[] = $results[0];
                }
            }

            //Clean Up Dups
            foreach ($specialData as $key => $value) {
                $nextKey = $key + 1;
                    if(isset($specialData[$nextKey])){
                        if($specialData[$key] == $specialData[$nextKey]){
                        unset($specialData[$key]);
                    }
                }
            }

            return $specialData;

        }

        private function getUserComissionInfo($affId)
        {
            //Get the users Comission Info
            $startDate = date('Y-m-d') . ' -12 months';
                $infStartDate = new DateTime($startDate .' 00:00:00',new DateTimeZone('America/New_York'));
                $infEndDate = new DateTime(date('Y-m-d') .' 00:00:00',new DateTimeZone('America/New_York'));
                $comissions = $this->externalApi->call('affiliates','affCommissions',[  $affId, $infStartDate, $infEndDate ]);

                return $comissions;
        }

}
