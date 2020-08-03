<?php

namespace App\Http\Controllers;

use App\Services\ExternalApiWrapper;
use App\User;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    protected $externalApi;

    public function __construct(ExternalApiWrapper $externalApi)
    {
        $this->externalApi = $externalApi;
    }

    public function index()
    {
        $user = Auth::user();

        $aff_info = $this->getAffInfo($user->api_id);
        if (isset($aff_info[0])) {
            $summary = $this->externalApi->generateRunningTotal($user->api_id);
            $referrals = $this->externalApi->findReferrals(['AffiliateId' => $aff_info[0]['Id']], ['ContactId']);
            sleep(.5);

            $referralIds = [];
            if (count($referrals)) {
                foreach ($referrals as $key => $value) {
                    array_push($referralIds, $value['ContactId']);
                }

                $contacts = $this->externalApi->rawQuery(['Id', 'FirstName', 'LastName', 'Phone1', 'Email', 'DateCreated', '_Status2', '_AppsPending', '_AppsApproved', '_FundingAmountApprovedsofar', 'Groups'], ['Id' => $referralIds]);
            } else {
                $contacts = [];
            }
        }

        if (isset($contacts)) {
            return view('home', ['aff_info' => $aff_info, 'contacts' => $contacts, 'user' => $user, 'summary' => $summary, 'ids' => $referralIds]);
        } else {
            return view('home', ['aff_info' => $aff_info, 'contacts' => [], 'user' => $user, 'summary' => [], 'ids' => []]);
        }
    }

    public function create(Request $request)
    {
        return view('profile', [
            'user' => $this->externalApi->rawQuery(['Id', 'FirstName', 'LastName', 'Phone1', 'Email'], ['Id' => $request->user()['api_id']])
        ]);
    }

    public function store(ContactSaveRequest $request)
    {
        $request->user()->updateContactInformation($request);

        $this->externalApi->updateContact($request->user());

        return redirect('/profile/');
    }

    public function update(UpdatePasswordRequest $request)
    {
        $data = $request->all();
        $user = Auth::user();

        $save = User::find($user['id']);

        $save->password = bcrypt($data['password']);

        $save->save();

        $contact = ['Password' => $data['password']];
        $contact_id = $this->externalApi->call('contacts', 'update', [$user['api_id'], $contact]);

        return redirect('/profile//');
    }

    private function getAffInfo($api_id)
    {
        $queryData = ['ContactId' => $api_id];
        $selectedFields = ['Id', 'AffCode'];
        $results = $this->externalApi->call('data', 'query', ['Affiliate', 1, 0, $queryData, $selectedFields, 'Id', false]);

        return $results;
    }

    private function getReferrals($comissions)
    {
        $specialData = [];

        foreach ($comissions as $comission => $value) {
            $queryData = ['Id' => $value['ContactId']];
            $selectedFields = ['Id', 'FirstName', 'LastName', 'Phone1', 'Email', 'DateCreated', '_Status2', '_AppsPending', '_AppsApproved', '_FundingAmountApprovedsofar', 'Groups'];
            $results = $this->externalApi->call('data', 'query', ['Contact', 1000, 0, $queryData, $selectedFields, 'Id', false]);
            if (isset($results[0])) {
                $specialData[] = $results[0];
            }
        }

        foreach ($specialData as $key => $value) {
            $nextKey = $key + 1;
            if (isset($specialData[$nextKey])) {
                if ($specialData[$key] == $specialData[$nextKey]) {
                    unset($specialData[$key]);
                }
            }
        }

        return $specialData;

    }

    private function getUserComissionInfo($affId)
    {
        $startDate = date('Y-m-d') . ' -12 months';
        $infStartDate = new DateTime($startDate . ' 00:00:00', new DateTimeZone('America/New_York'));
        $infEndDate = new DateTime(date('Y-m-d') . ' 00:00:00', new DateTimeZone('America/New_York'));
        $comissions = $this->externalApi->call('affiliates', 'affCommissions', [$affId, $infStartDate, $infEndDate]);

        return $comissions;
    }

}
