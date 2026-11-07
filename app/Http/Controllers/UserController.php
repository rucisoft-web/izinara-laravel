<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'users' => User::with('party')->withCount(['tasks as open_tasks_count' => fn ($q) => $q->where('status', '!=', 'selesai')])->orderBy('party_id')->orderBy('name')->get(),
            'parties' => Party::withCount('users')->orderBy('sequence')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin();
        $data = $request->validate([
            'name' => 'required|string|max:255', 'username' => 'required|string|max:60|alpha_dash:ascii|unique:users,username',
            'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:6',
            'party_id' => 'nullable|exists:parties,id', 'role' => 'required|in:'.implode(',', array_keys(User::ROLES)),
            'job_title' => 'nullable|string|max:255', 'phone' => 'nullable|string|max:40', 'weekly_capacity' => 'required|integer|min:1|max:7',
        ]);
        User::create($data);
        return back()->with('ok', 'Pengguna ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $this->requireAdmin();
        $data = $request->validate([
            'name' => 'required|string|max:255', 'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'party_id' => 'nullable|exists:parties,id', 'role' => 'required|in:'.implode(',', array_keys(User::ROLES)),
            'job_title' => 'nullable|string|max:255', 'phone' => 'nullable|string|max:40', 'weekly_capacity' => 'required|integer|min:1|max:7',
            'active' => 'nullable|boolean', 'password' => 'nullable|string|min:6',
        ]);
        $data['active'] = $request->boolean('active');
        if (empty($data['password'])) unset($data['password']);
        $user->update($data);
        return back()->with('ok', 'Pengguna diperbarui.');
    }

    public function updateParty(Request $request, Party $party)
    {
        $this->requireAdmin();
        $party->update($request->validate(['name' => 'required|string|max:255', 'responsibility' => 'nullable|string']));
        return back()->with('ok', 'Bagian diperbarui.');
    }
}
