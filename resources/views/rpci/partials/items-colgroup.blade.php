{{--
    Single source of truth for the RPCI items table column widths.

    Both the body table and the separate TOTALS table include this partial, so
    their column geometry is guaranteed to be identical. Widths live here and
    nowhere else — do NOT reintroduce per-cell width classes, they would become
    a second, drifting source of truth.

    The 10 columns must stay in this exact order:
        1 Article            6 Balance Per Card (Qty)
        2 Description        7 On Hand Per Count (Qty)
        3 Stock Number       8 Shortage / Overage (Qty)
        4 Unit of Measure    9 Shortage / Overage (Value)
        5 Unit Value        10 Remarks
--}}
<colgroup>
    <col style="width: 8%;">   {{-- 1  Article --}}
    <col style="width: 16%;">  {{-- 2  Description --}}
    <col style="width: 8%;">   {{-- 3  Stock Number --}}
    <col style="width: 7%;">   {{-- 4  Unit of Measure --}}
    <col style="width: 9%;">   {{-- 5  Unit Value --}}
    <col style="width: 9%;">   {{-- 6  Balance Per Card (Qty) --}}
    <col style="width: 9%;">   {{-- 7  On Hand Per Count (Qty) --}}
    <col style="width: 8%;">   {{-- 8  Shortage / Overage (Qty) --}}
    <col style="width: 9%;">   {{-- 9  Shortage / Overage (Value) --}}
    <col style="width: 17%;">  {{-- 10 Remarks --}}
</colgroup>
