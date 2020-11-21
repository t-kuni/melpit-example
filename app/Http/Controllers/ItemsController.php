<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemsController extends Controller
{
    public function showItems(Request $request)
    {
        $query = Item::query();

        // カテゴリで絞り込み
        if ($request->filled('category')) {
            list($categoryType, $categoryID) = explode(':', $request->input('category'));

            if ($categoryType === 'primary') {
                $query->whereHas('secondaryCategory.primaryCategory', function ($query) use ($categoryID) {
                    $query->where('id', $categoryID);
                });
            } else if ($categoryType === 'secondary') {
                $query->whereHas('secondaryCategory', function ($query) use ($categoryID) {
                    $query->where('id', $categoryID);
                });
            }
        }

        // キーワードで絞り込み
        if ($request->filled('keyword')) {
            $keyword = '%' . $this->escape($request->input('keyword')) . '%';
            $query->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', $keyword);
                $query->orWhere('description', 'LIKE', $keyword);
            });
        }

        $items = $query->orderByRaw( "FIELD(state, '" . Item::STATE_SELLING . "', '" . Item::STATE_BOUGHT . "')" )
            ->orderBy('id', 'DESC')
            ->paginate(52);

        return view('items.items')
            ->with('items', $items);
    }

    private function escape(string $value, string $char = '\\')
    {
        return str_replace(
            [$char, '%', '_'],
            [$char . $char, $char . '%', $char . '_'],
            $value
        );
    }

    public function showItemDetail(Item $item)
    {
        return view('items.item_detail')
            ->with('item', $item);
    }
}
