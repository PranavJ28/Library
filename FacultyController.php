<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Fine;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function profile()
    {
        return view('faculty.profile');
    }

    public function update_password_view()
    {
        return view('faculty.update-password');
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
        return view('faculty.books', compact('books'));
    }

    public function request_book(Request $request, Book $book)
    {
        $already_requested = Borrowing::where('book_id', $book->id)
            ->where('borrowed_by', $request->user()->id)
            ->where('status', 'pending')
            ->exists();

        if ($already_requested) {
            return back()->with('Error:' . $book->id, 'You have already requested this book');
        }

        $already_borrowed = Borrowing::where('book_id', $book->id)
            ->where('borrowed_by', $request->user()->id)
            ->where('status', 'confirmed')
            ->exists();

        if ($already_borrowed) {
            return back()->with('Error:' . $book->id, 'You have already borrowed this book');
        }

        if ($book->quantity <= 0) {
            return back()->with('Error:' . $book->id, 'Book out of stock');
        }

        Borrowing::create([
            'book_id' => $book->id,
            'borrowed_by' => $request->user()->id,
        ]);

        return back()->with('Success:' . $book->id, 'Book requested successfully');
    }

    public function view_borrowings(Request $request)
    {
        $borrowings = Borrowing::where('borrowed_by', $request->user()->id)->get();
        return view('faculty.borrowings', compact('borrowings'));
    }

    public function cancel_borrow_request(Borrowing $borrowing)
    {
        if ($borrowing->status === 'confirmed') {
            return back()->with('Error:' . $borrowing->id, 'You cannot cancel a confirmed borrowing');
        }

        $borrowing->update(['status' => 'cancelled']);
        return back()->with('Success:' . $borrowing->id, 'Borrow request cancelled successfully');
    }

    public function pay_fine(Fine $fine)
    {
        $fine->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return back()->with('success', 'Fine collected successfully');
    }
}
