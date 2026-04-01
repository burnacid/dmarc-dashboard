<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImapAccountRequest;
use App\Http\Requests\UpdateImapAccountRequest;
use App\Models\ImapAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ImapAccountController extends Controller
{
    public function index(): View
    {
        $accounts = auth()->user()->imapAccounts()
            ->withCount('reports')
            ->latest()
            ->get();

        return view('imap-accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('imap-accounts.create', [
            'account' => new ImapAccount([
                'port' => 993,
                'encryption' => 'ssl',
                'folder' => 'INBOX',
                'search_criteria' => 'UNSEEN',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(StoreImapAccountRequest $request): RedirectResponse
    {
        $request->user()->imapAccounts()->create($request->validated());

        return to_route('imap-accounts.index')->with('status', 'IMAP account created successfully.');
    }

    public function show(ImapAccount $imapAccount): RedirectResponse
    {
        return to_route('imap-accounts.edit', $this->ownedAccount($imapAccount));
    }

    public function edit(ImapAccount $imapAccount): View
    {
        return view('imap-accounts.edit', [
            'account' => $this->ownedAccount($imapAccount),
        ]);
    }

    public function update(UpdateImapAccountRequest $request, ImapAccount $imapAccount): RedirectResponse
    {
        $account = $this->ownedAccount($imapAccount);
        $validated = $request->validated();

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $account->update($validated);

        return to_route('imap-accounts.index')->with('status', 'IMAP account updated successfully.');
    }

    public function destroy(ImapAccount $imapAccount): RedirectResponse
    {
        $this->ownedAccount($imapAccount)->delete();

        return to_route('imap-accounts.index')->with('status', 'IMAP account removed.');
    }

    private function ownedAccount(ImapAccount $imapAccount): ImapAccount
    {
        abort_unless($imapAccount->user_id === auth()->id(), 404);

        return $imapAccount;
    }
}
