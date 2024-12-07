<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Fine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function profile()
    {
        return view('admin.profile');
    }

    public function add_book_view()
    {
        return view('admin.add-book');
    }

    public function add_book(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'title' => 'required|string',
            'author' => 'required|string',
            'isbn' => 'required|string|unique:books',
            'category' => 'required|in:' . implode(',', config('bookcategories')),
            'publisher' => 'required|string',
            'publication_date' => 'required|date_format:Y',
            'description' => 'nullable|string',
            'quantity' => 'required|integer',
        ]);

        if ($validated->fails()) {
            return redirect()->back()->withErrors($validated)->withInput();
        }

        Book::create([
            'title' => $request->title,
            'author' => $request->author,
            'isbn' => $request->isbn,
            'category' => $request->category,
            'publisher' => $request->publisher,
            'publication_date' => $request->publication_date,
            'quantity' => $request->quantity,
            'description' => $request->description,
            'added_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.add_book_view')->with('success', 'Book added successfully');
    }

    public function view_books(Request $request)
    {
        $query = Book::query();

        if ($request->has('q')) {
            $search = $request->input('q');
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhere('isbn', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")
                ->orWhere('publisher', 'like', "%{$search}%");
        }

        $books = $query->get();
        return view('admin.books', compact('books'));
    }

    public function edit_book_view(Book $book)
    {
        return view('admin.edit-book', compact('book'));
    }

    public function edit_book(Request $request, Book $book)
    {
        $validated = Validator::make($request->all(), [
            'title' => 'required|string',
            'author' => 'required|string',
            'isbn' => 'required|string|unique:books,isbn,' . $book->id,
            'category' => 'required|in:' . implode(',', config('bookcategories')),
            'publisher' => 'required|string',
            'publication_date' => 'required|date_format:Y',
            'description' => 'nullable|string',
            'quantity' => 'required|integer',
        ]);

        if ($validated->fails()) {
            return redirect()->back()->withErrors($validated)->withInput();
        }

        $book->update([
            'title' => $request->title,
            'author' => $request->author,
            'isbn' => $request->isbn,
            'category' => $request->category,
            'publisher' => $request->publisher,
            'publication_date' => $request->publication_date,
            'quantity' => $request->quantity,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.edit_book_view', $book)->with('success', 'Book updated successfully');
    }

    public function delete_book(Book $book)
    {
        $book->delete();
        return redirect()->route('admin.view_books')->with('success', 'Book deleted successfully');
    }

    public function view_requests()
    {
        $borrow_requests = Borrowing::where('status', 'pending')->get();
        return view('admin.requests', compact('borrow_requests'));
    }

    public function issue_book(Request $request, Borrowing $borrowing)
    {
        $existing_borrowing = Borrowing::where('borrowed_by', $borrowing->borrowed_by)
            ->where('status', 'confirmed')
            ->exists();

        if ($existing_borrowing) {
            return back()->with('error', 'User already has a book issued');
        }

        if ($borrowing->book->quantity <= 0) {
            return back()->with('error', 'Book out of stock');
        }

        $borrowing->update([
            'status' => 'confirmed',
            'lended_by' => $request->user()->id,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(7)
        ]);

        $borrowing->book->decrement('quantity');

        return back()->with('success', 'Book issued successfully');
    }

    public function view_lendings()
    {
        $lendings = Borrowing::where('status', 'confirmed')->orWhere('status', 'returned')->get();
        return view('admin.lendings', compact('lendings'));
    }

    public function return_book(Borrowing $borrowing)
    {
        $borrowing->update([
            'status' => 'returned',
            'returned_at' => now()
        ]);

        $borrowing->book->increment('quantity');

        $due_date = $borrowing->due_at;
        $return_date = $borrowing->returned_at;

        if ($return_date > $due_date) {
            $days_overdue = $return_date->diffInDays($due_date);
            $fine = $days_overdue * 0.25;
            Fine::create([
                'borrowing_id' => $borrowing->id,
                'amount' => abs($fine)
            ]);
        }

        return back()->with('Success:' . $borrowing->id, 'Book returned successfully');
    }

    public function collect_fine(Fine $fine)
    {
        $fine->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return back()->with('success', 'Fine collected successfully');
    }

    public function update_password_view()
    {
        return view('admin.update-password');
    }
}
