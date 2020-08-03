<?php

namespace App\Http\Controllers;

use App\Services\ExternalApiWrapper;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    protected $externalApi;

    public function __construct(ExternalApiWrapper $externalApi)
    {
        $this->externalApi = $externalApi;
    }

    public function index(Request $request)
    {
        $affiliate_id = $this->externalApi->getAffiliateId($request->user()->api_id);
        if (!$affiliate_id) {
            return view('home', ['aff_info' => null, 'contacts' => [], 'user' => $request->user(), 'summary' => [], 'ids' => []]);
        }


        $summary = $this->externalApi->generateRunningTotal($request->user()->api_id);
        $referrals = $this->externalApi->findReferrals(['AffiliateId' => $affiliate_id[0]['Id']], ['ContactId']);
        sleep(.5);


        $referral_ids = $referrals->pluck('ContactId');
        $contacts = $this->externalApi->rawQuery(
            ['Id', 'FirstName', 'LastName', 'Phone1', 'Email', 'DateCreated', '_Status2', '_AppsPending', '_AppsApproved', '_FundingAmountApprovedsofar', 'Groups'],
            ['Id' => $referral_ids]);

        return view('home', ['aff_info' => $affiliate_id, 'contacts' => $contacts, 'user' => $request->user(), 'summary' => $summary, 'ids' => $referral_ids]);
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
        $request->user()->changePassword();

        $this->externalApi->updatePassword($request->user()->api_id, $request->input('password'));

        return redirect('/profile/');
    }
}
