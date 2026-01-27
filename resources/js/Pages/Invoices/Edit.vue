<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/Form/SearchableSelect.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    invoice: Object,
    products: Array,
    schemes: Array,
    availableStocks: { type: Array, default: () => [] }
});

// -- State --
const selectedCustomer = ref(props.invoice.customer || null);
const productCode = ref('');
const selectedProduct = ref(null);

const productCodeRef = ref(null);
const cartonsRef = ref(null);
const piecesRef = ref(null);
const schemeRef = ref(null);

const discountSchemes = ref([]);
const productSchemes = ref([]);

// Product Entry Item
const newItem = ref({
    product_id: '',
    stock_id: '',
    cartons: 0,
    pieces: 0,
    total_pieces: 0,
    exclusive_price: 0,
    fed_percent: 0,
    sales_tax_percent: 0,
    extra_tax_percent: 0,
    adv_tax_percent: selectedCustomer.value?.adv_tax_percent || 0,
    net_unit_price: 0,
    scheme_id: '',
    scheme_discount: 0,
    discount_scheme_id: '',
    free_product: null,
    free_pieces: 0,
    manual_discount_percent: 0,
    manual_discount_amount: 0,
    batch_number: '',
    available_qty: 0,
    trade_discount_percent: 0,
    is_net_fixed: false,
    is_free: false
});

// Form Initialization
const form = useForm({
    invoice_type: props.invoice.invoice_type,
    is_credit: Boolean(props.invoice.is_credit),
    notes: props.invoice.notes || '',
    
    // Fixed fields (Read-only context)
    distribution_id: props.invoice.distribution_id,
    van_id: props.invoice.van_id,
    order_booker_id: props.invoice.order_booker_id,
    customer_id: props.invoice.customer_id,
    invoice_date: props.invoice.invoice_date,

    items: props.invoice.items.map(item => ({
        id: item.id,
        product_id: item.product_id,
        product_name: item.product?.name,
        product_code: item.product?.dms_code || item.product?.sku,
        brand_id: item.product?.brand_id,
        brand_name: item.product?.brand?.name,
        stock_id: null, // Existing items don't track stock_id in UI easily unless we fetch it. Backend handles it.
        batch_number: null, // Same
        cartons: item.cartons,
        pieces: item.pieces,
        total_pieces: item.total_pieces,
        exclusive_price: parseFloat(item.list_price_before_tax) || parseFloat(item.price),
        fed_percent: parseFloat(item.fed_percent),
        fed_amount: parseFloat(item.fed_amount),
        sales_tax_percent: parseFloat(item.tax_percent),
        sales_tax_amount: parseFloat(item.tax),
        extra_tax_percent: parseFloat(item.extra_tax_percent),
        extra_tax_amount: parseFloat(item.extra_tax_amount),
        adv_tax_percent: parseFloat(item.adv_tax_percent),
        adv_tax_amount: parseFloat(item.adv_tax_amount),
        gross_amount: parseFloat(item.gross_amount),
        net_unit_price: parseFloat(item.price),
        
        scheme_id: item.scheme_id,
        scheme_name: item.scheme?.name || (item.is_free ? 'FREE' : null),
        scheme_discount: parseFloat(item.scheme_discount),
        discount_scheme_id: item.scheme_id,
        
        manual_discount_percent: 0, // Not explicitly stored
        manual_discount_amount: parseFloat(item.discount) - parseFloat(item.scheme_discount),
        total_discount: parseFloat(item.discount),
        trade_discount_amount: parseFloat(item.retail_margin),
        trade_discount_percent: 0, // Not explicitly stored
        
        is_free: Boolean(item.is_free),
        brand_scheme_brand_id: item.brand_scheme_brand_id // If column exists? Likely not in prop?
    }))
});

// -- Computed --

// Brand Quantities
const brandQuantities = computed(() => {
    const quantities = {};
    form.items.forEach(item => {
        if (item.is_free) return;
        const brandId = item.brand_id;
        if (brandId) {
            quantities[brandId] = (quantities[brandId] || 0) + item.total_pieces;
        }
    });
    return quantities;
});

const productOptions = computed(() => {
    return props.products.map(p => ({
        ...p,
        displayLabel: `${p.dms_code || p.sku || ''} - ${p.name}`
    }));
});

const availableBatches = computed(() => {
    if (!newItem.value.product_id) return [];
    let stocks = props.availableStocks.filter(s => Number(s.product_id) === Number(newItem.value.product_id));
    if (form.distribution_id) {
        stocks = stocks.filter(s => Number(s.distribution_id) === Number(form.distribution_id));
    }
    return stocks.map(s => ({
        ...s,
        label: `${s.batch_number ? 'Batch: ' + s.batch_number : 'No Batch'} ${s.location ? '(' + s.location + ')' : ''} - Qty: ${s.quantity}`,
    }));
});

const isStockExceeded = computed(() => {
    if (!newItem.value.stock_id || newItem.value.available_qty <= 0) return false;
    return newItem.value.total_pieces > newItem.value.available_qty;
});

// -- Methods (Parity with Create.vue) --

const calculateNetUnitPrice = () => {
    const exclusive = parseFloat(newItem.value.exclusive_price) || 0;
    const fed = parseFloat(newItem.value.fed_percent) / 100 || 0;
    const salesTax = parseFloat(newItem.value.sales_tax_percent) / 100 || 0;
    const extraTax = parseFloat(newItem.value.extra_tax_percent) / 100 || 0;

    const productTypeName = (selectedProduct.value?.product_type?.name || '').toLowerCase().trim();
    const isFood = productTypeName === 'food';

    let netPrice;
    if (isFood) {
        netPrice = exclusive * (1 + salesTax + extraTax);
    } else {
        netPrice = exclusive * (1 + fed) * (1 + salesTax);
    }
    newItem.value.net_unit_price = netPrice;
};

const calculateReversePrice = () => {
    const netPrice = parseFloat(newItem.value.net_unit_price) || 0;
    const fed = parseFloat(newItem.value.fed_percent) / 100 || 0;
    const salesTax = parseFloat(newItem.value.sales_tax_percent) / 100 || 0;
    const extraTax = parseFloat(newItem.value.extra_tax_percent) / 100 || 0;

    const productTypeName = (selectedProduct.value?.product_type?.name || '').toLowerCase().trim();
    const isFood = productTypeName === 'food';

    let divisor;
    if (isFood) {
        divisor = 1 + salesTax + extraTax;
    } else {
        divisor = (1 + fed) * (1 + salesTax);
    }

    newItem.value.exclusive_price = (divisor === 0 || divisor === 1) ? netPrice : (netPrice / divisor);
};

const handleTaxChange = () => {
    if (newItem.value.is_net_fixed) {
        calculateReversePrice();
    } else {
        calculateNetUnitPrice();
    }
};

const onExclusivePriceInput = () => {
    newItem.value.is_net_fixed = false;
    calculateNetUnitPrice();
};

// -- Schemes Loading --
const loadDiscountSchemes = async (productId, quantity, brandId = null) => {
    try {
        let brandQuantity = quantity;
        if (brandId) {
            brandQuantity = (brandQuantities.value[brandId] || 0) + quantity;
        }

        const subDistributionId = selectedCustomer.value?.sub_distribution_id || null;

        const response = await axios.get(route('api.discount-schemes', productId), {
            params: {
                quantity,
                brand_quantity: brandQuantity,
                sub_distribution_id: subDistributionId
            }
        });
        discountSchemes.value = response.data;
        
        // Clear selection to avoid stale state
        newItem.value.discount_scheme_id = '';
        newItem.value.scheme_discount = 0;
        newItem.value.free_product = null;
        newItem.value.free_pieces = 0;
    } catch (e) {
        discountSchemes.value = [];
    }
};

const applyDiscountScheme = (scheme) => {
    if (!scheme) {
        newItem.value.discount_scheme_id = '';
        newItem.value.scheme_discount = 0;
        newItem.value.free_product = null;
        newItem.value.free_pieces = 0;
        return;
    }

    newItem.value.discount_scheme_id = scheme.id;

    if (scheme.discount_type === 'amount_less') {
        newItem.value.scheme_discount = parseFloat(scheme.amount_less) || 0;
        newItem.value.free_product = null;
    } else if (scheme.discount_type === 'free_product') {
        newItem.value.scheme_discount = 0;
        newItem.value.free_product = scheme.free_product;
        newItem.value.free_pieces = scheme.free_pieces || 0;
    } else if (scheme.discount_type === 'percentage') {
         const baseAmount = newItem.value.total_pieces * newItem.value.net_unit_price;
         newItem.value.scheme_discount = baseAmount * (scheme.discount_value / 100);
         newItem.value.free_product = null;
    }
};

// -- Watchers --
watch(() => newItem.value.product_id, (newVal) => {
    if (newVal) {
        const product = props.products.find(p => p.id === newVal);
        if (product) {
            selectedProduct.value = product;
            newItem.value.stock_id = '';
            newItem.value.batch_number = '';
            newItem.value.available_qty = 0;
            
            newItem.value.exclusive_price = parseFloat(product.list_price_before_tax) || 0;
            newItem.value.fed_percent = parseFloat(product.fed_percent) || 0;
            newItem.value.sales_tax_percent = parseFloat(product.fed_sales_tax) || 0;
            newItem.value.extra_tax_percent = parseFloat(product.product_type?.extra_tax) || 0;
            newItem.value.trade_discount_percent = parseFloat(product.retail_margin) || 0;
            newItem.value.adv_tax_percent = parseFloat(selectedCustomer.value?.adv_tax_percent) || 0;

            const productTypeName = (product.product_type?.name || '').toLowerCase();
            const isFood = productTypeName === 'food';

            if (isFood) {
                newItem.value.is_net_fixed = false;
                calculateNetUnitPrice();
            } else {
                newItem.value.net_unit_price = parseFloat(product.unit_price) || 0;
                newItem.value.is_net_fixed = true;
                calculateReversePrice();
            }

            productCode.value = product.dms_code || product.sku || '';

            // Load Schemes
            // loadProductSchemes(newVal); // Only needed for dropdown list if separate? Create.vue puts it all in discountSchemes usually?
            // Actually Create.vue has `productSchemes` AND `discountSchemes`.
            // `productSchemes` seems to be "Schemes available for this product generally" vs "Schemes applicable now".
            // I'll skip productSchemes purely visual list if not used for logic.
            // Create.vue uses `productSchemes` in watch(scheme_id).
            // But `loadDiscountSchemes` populates `discountSchemes`.
            // I'll trust `loadDiscountSchemes`.
            
             // FIFO Selection
             const stocks = availableBatches.value;
             if (stocks.length > 0) {
                 newItem.value.stock_id = stocks[0].id; // Oldest
             } else {
                 Swal.fire({
                    icon: 'warning',
                    title: 'Stock Not Available',
                    text: `No stock available for "${product.name}".`,
                    confirmButtonColor: '#059669'
                 });
             }
             
             // Focus
             setTimeout(() => cartonsRef.value?.focus(), 100);
        }
    } else {
        selectedProduct.value = null;
        discountSchemes.value = [];
    }
});

watch(() => newItem.value.stock_id, (newVal) => {
    const stock = availableBatches.value.find(s => s.id === newVal);
    if (stock) {
        newItem.value.batch_number = stock.batch_number;
        newItem.value.available_qty = stock.quantity;
    } else {
        newItem.value.batch_number = '';
        newItem.value.available_qty = 0;
    }
});

watch([() => newItem.value.cartons, () => newItem.value.pieces], () => {
    const packing = selectedProduct.value?.pieces_per_packing || selectedProduct.value?.packing?.quantity || 12;
    newItem.value.total_pieces = (newItem.value.cartons * packing) + newItem.value.pieces;
    
    if (newItem.value.product_id && newItem.value.total_pieces > 0) {
        const brandId = selectedProduct.value?.brand_id;
        loadDiscountSchemes(newItem.value.product_id, newItem.value.total_pieces, brandId);
    }
});

// -- Add Item Logic --
const addItem = () => {
    if (!newItem.value.product_id || newItem.value.total_pieces <= 0) return;

    // Duplicate Check
    const isDuplicate = form.items.some(item => 
        item.product_id === parseInt(newItem.value.product_id) && !item.is_free
    );
    if (isDuplicate) {
        Swal.fire({
            icon: 'error',
            title: 'Product Already Added',
            text: `"${selectedProduct.value?.name}" is already in the invoice. Please remove and re-add to modify.`,
            confirmButtonColor: '#059669'
        });
        return;
    }

    // Stock Validation
    if (newItem.value.stock_id && newItem.value.available_qty > 0) {
        // Since we don't have existing usage calc easily for Edit mode (unless we assume existing items are from same batch? Unlikely/Hard to match),
        // we'll just check against TOTAL available.
        // For strict correctness, we should subtract "locked" stock by other lines? 
        // In Edit mode, "Available Stock" usually means "Current unallocated stock". 
        // Any stock in *this* invoice that we are editing is theoretically "Allocated".
        // BUT `availableStocks` passed from Controller might exclude what allowed this invoice?
        // Actually, if we reverse the stock in backend, then effectively all stock is available. But in UI we see `availableStocks` (which likely doesn't count this invoice's deduction if it was already deducted).
        // If we heavily edited and kept old items, double counting?
        // Backend `InvoiceController::edit` filters `Stock` where `quantity > 0`.
        // The stock currently "held" by this invoice IS deducted in DB. 
        // So `availableStocks` shows what is REMAINING.
        // So if I have 10 items in this invoice, and 0 in warehouse. `availableStocks` says 0.
        // If I delete the item, stock returns.
        // If I EDIT (remove & add), I am adding NEW items against REMAINING stock.
        // This is a UX issue: If I want to increase quantity of an item from 10 to 12.
        // I delete (10). Stock becomes 10 (in theory, but UI doesn't refresh stock).
        // Then I add 12. UI says "Available: 0". I can't add 12.
        // THIS IS A BLOCKER for pure "Delete & Add" logic without dynamic stock refresh.
        // However, I can allow "Overdraft" in UI if I warn? Or fetch updated stock?
        // Or, I just let the backend handle the error?
        // The user says "same as Create". Create enforces stock.
        // Constraint: Edit page should ideally show "Stock + Current Invoice Qty".
        // I don't have that easily.
        // I will proceed with standard check, but maybe allow user to override with confirmation?
        // "Create.vue" checks `available_qty`.
        // I will do strict check. If user is stuck, they delete item, SAVE (stock returns), then Edit again.
        // OR: I can imply that `available_qty` is just a guide.
        // Let's implement strict check but with a "Force" option? No, Create doesn't have force.
        // I'll stick to strict check. It's safer.
        if (newItem.value.total_pieces > newItem.value.available_qty) {
             Swal.fire({
                icon: 'warning',
                title: 'Insufficient Stock',
                html: `Requested: ${newItem.value.total_pieces}<br>Available: ${newItem.value.available_qty}<br><br><b>Note:</b> If you are editing an existing quantity, please Remove the item, Save, and then Add it again with new quantity to ensure stock accuracy.`,
                confirmButtonColor: '#059669'
             });
             // return; // Allow bypass? No, return.
             return;
        }
    }

    // Prepare Item
    const product = selectedProduct.value;
    const exclusiveAmount = newItem.value.exclusive_price * newItem.value.total_pieces;
    const fedAmount = exclusiveAmount * (newItem.value.fed_percent / 100);
    const salesTaxAmount = (exclusiveAmount + fedAmount) * (newItem.value.sales_tax_percent / 100);
    const extraTaxAmount = exclusiveAmount * ((newItem.value.extra_tax_percent || 0) / 100);
    const grossAmount = exclusiveAmount + fedAmount + salesTaxAmount + extraTaxAmount;

    // Trade Discount
    const tradeDiscountAmount = (grossAmount / (1 + newItem.value.trade_discount_percent / 100)) * (newItem.value.trade_discount_percent / 100);
    
    // Manual + Scheme Discount
    const manualDiscountVal = (grossAmount * (newItem.value.manual_discount_percent / 100)) + newItem.value.manual_discount_amount;
    const totalDiscount = (newItem.value.scheme_discount || 0) + manualDiscountVal;
    
    // Adv Tax
    const netAmountForTax = grossAmount - tradeDiscountAmount - totalDiscount;
    const advTaxAmount = netAmountForTax * (newItem.value.adv_tax_percent / 100);

    form.items.push({
        id: null, // New Item
        product_id: product.id,
        product_name: product.name,
        product_code: product.dms_code || product.sku,
        brand_id: product.brand_id,
        brand_name: product.brand?.name,
        stock_id: newItem.value.stock_id,
        batch_number: newItem.value.batch_number,
        cartons: newItem.value.cartons,
        pieces: newItem.value.pieces,
        total_pieces: newItem.value.total_pieces,
        exclusive_price: newItem.value.exclusive_price,
        fed_percent: newItem.value.fed_percent,
        fed_amount: fedAmount,
        sales_tax_percent: newItem.value.sales_tax_percent,
        sales_tax_amount: salesTaxAmount,
        extra_tax_percent: newItem.value.extra_tax_percent,
        extra_tax_amount: extraTaxAmount,
        adv_tax_percent: newItem.value.adv_tax_percent,
        adv_tax_amount: advTaxAmount,
        gross_amount: grossAmount,
        net_unit_price: newItem.value.net_unit_price,
        scheme_id: newItem.value.scheme_id || null,
        scheme_name: discountSchemes.value.find(s => s.id == newItem.value.scheme_id)?.name,
        scheme_discount: newItem.value.scheme_discount,
        manual_discount_amount: manualDiscountVal,
        total_discount: totalDiscount,
        trade_discount_percent: newItem.value.trade_discount_percent,
        trade_discount_amount: tradeDiscountAmount,
        is_free: false
    });

    // Free Product
    if (newItem.value.free_product && newItem.value.free_pieces > 0) {
        addFreeItem(newItem.value.free_product, newItem.value.free_pieces, newItem.value.discount_scheme_id);
    }

    resetNewItem();
};

const addFreeItem = (freeProduct, qty, schemeId) => {
    // Check if free item exists
    const appliedScheme = discountSchemes.value.find(s => s.id === schemeId);
    const isBrandScheme = appliedScheme?.scheme_type === 'brand';
    const schemeBrandId = isBrandScheme ? (selectedProduct.value?.brand_id) : null;

    const existingIndex = form.items.findIndex(i => {
         if (!i.is_free) return false;
         if (isBrandScheme && i.brand_scheme_brand_id === schemeBrandId) return true;
         if (i.product_id === freeProduct.id) return true;
         return false;
    });

    if (existingIndex >= 0) {
         // Update
         const item = form.items[existingIndex];
         if (isBrandScheme) {
             item.total_pieces = qty;
         } else {
             item.total_pieces += qty;
         }
         item.pieces = item.total_pieces;
         // Recalc Free amounts (using existing item logic or new? New is safer)
         // Assuming item.exclusive_price is correct
         const excl = item.exclusive_price * item.total_pieces;
         const fed = excl * (item.fed_percent/100);
         const sales = (excl + fed) * (item.sales_tax_percent/100);
         const extra = excl * (item.extra_tax_percent/100);
         const gross = excl + fed + sales + extra;
         item.fed_amount = fed;
         item.sales_tax_amount = sales;
         item.extra_tax_amount = extra;
         item.gross_amount = gross;
         item.trade_discount_amount = gross; // Free
    } else {
         // Add New
         const isSame = freeProduct.id === selectedProduct.value.id;
         const exclPrice = parseFloat(freeProduct.list_price_before_tax) || 0;
         const fedPct = isSame ? newItem.value.fed_percent : (parseFloat(freeProduct.fed_percent)||0);
         const salesPct = isSame ? newItem.value.sales_tax_percent : (parseFloat(freeProduct.fed_sales_tax)||0);
         const extraPct = isSame ? newItem.value.extra_tax_percent : (parseFloat(freeProduct.product_type?.extra_tax)||0);
         
         const excl = exclPrice * qty;
         const fed = excl * (fedPct/100);
         const sales = (excl + fed) * (salesPct/100);
         const extra = excl * (extraPct/100);
         const gross = excl + fed + sales + extra;
         const netUnit = exclPrice * (1+fedPct/100) * (1+salesPct/100); 

         form.items.push({
             id: null,
             product_id: freeProduct.id,
             product_name: freeProduct.name + ' (FREE)',
             product_code: freeProduct.dms_code,
             stock_id: null, 
             cartons: 0,
             pieces: qty,
             total_pieces: qty,
             exclusive_price: exclPrice,
             fed_percent: fedPct, fed_amount: fed,
             sales_tax_percent: salesPct, sales_tax_amount: sales,
             extra_tax_percent: extraPct, extra_tax_amount: extra,
             adv_tax_percent: 0, adv_tax_amount: 0,
             gross_amount: gross,
             net_unit_price: netUnit,
             scheme_name: 'FREE',
             trade_discount_amount: gross,
             is_free: true,
             brand_scheme_brand_id: schemeBrandId
         });
    }
};

const resetNewItem = () => {
    newItem.value = {
        product_id: '',
        stock_id: '',
        cartons: 0,
        pieces: 0,
        total_pieces: 0,
        exclusive_price: 0,
        fed_percent: 0,
        sales_tax_percent: 0,
        extra_tax_percent: 0,
        adv_tax_percent: selectedCustomer.value?.adv_tax_percent || 0,
        net_unit_price: 0,
        scheme_id: '',
        scheme_discount: 0,
        discount_scheme_id: '',
        free_product: null,
        free_pieces: 0,
        manual_discount_percent: 0,
        manual_discount_amount: 0,
        batch_number: '',
        available_qty: 0,
        trade_discount_percent: 0,
        is_net_fixed: false,
        is_free: false
    };
    discountSchemes.value = [];
    productCode.value = '';
    setTimeout(() => productCodeRef.value?.focus(), 100);
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const searchProductByCode = () => {
    const found = props.products.find(p => 
        (p.dms_code && p.dms_code.toLowerCase() === productCode.value.toLowerCase()) || 
        (p.sku && p.sku.toLowerCase() === productCode.value.toLowerCase())
    );
    if (found) {
        newItem.value.product_id = found.id;
    }
};

const submit = () => {
    if (form.items.length === 0) return;
    form.put(route('invoices.update', props.invoice.id), {
         onSuccess: () => Swal.fire('Success', 'Invoice Updated', 'success')
    });
};

const formatAmount = (val) => new Intl.NumberFormat('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(val || 0);

// Helper display getters
const getItemExclusive = (item) => item.exclusive_price * item.total_pieces;
const getItemFed = (item) => item.fed_amount || (getItemExclusive(item) * item.fed_percent/100); 
const getItemSalesTax = (item) => item.sales_tax_amount || ((getItemExclusive(item) + getItemFed(item)) * item.sales_tax_percent/100);
const getItemExtraTax = (item) => item.extra_tax_amount || (getItemExclusive(item) * item.extra_tax_percent/100);
const getItemAdvTax = (item) => item.adv_tax_amount || 0; 
const getItemDiscount = (item) => (item.total_discount || 0);

// Totals
const totalExclusive = computed(() => form.items.reduce((s, i) => s + getItemExclusive(i), 0));
const totalFed = computed(() => form.items.reduce((s, i) => s + getItemFed(i), 0));
const totalSalesTax = computed(() => form.items.reduce((s, i) => s + getItemSalesTax(i), 0));
const totalExtraTax = computed(() => form.items.reduce((s, i) => s + getItemExtraTax(i), 0));
const totalAdvTax = computed(() => form.items.reduce((s, i) => s + getItemAdvTax(i), 0));
const totalGrossAmount = computed(() => form.items.reduce((s, i) => s + parseFloat(i.gross_amount || 0), 0));
const totalDiscount = computed(() => form.items.reduce((s, i) => s + (i.total_discount || 0), 0));
const totalTradeDiscount = computed(() => form.items.reduce((s, i) => s + (i.trade_discount_amount || 0), 0));
const grandTotal = computed(() => totalGrossAmount.value - totalDiscount.value - totalTradeDiscount.value + totalAdvTax.value);

// F2
const handleKeydown = (e) => {
    if (e.key === 'F2') { e.preventDefault(); submit(); }
};
onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => document.removeEventListener('keydown', handleKeydown));

</script>

<template>
    <DashboardLayout>
        <div class="space-y-6 max-w-7xl mx-auto">
             <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Invoice: {{ invoice.invoice_number }}</h1>
                    <p class="text-gray-500 mt-1">Modify invoice details. Press F2 to save.</p>
                </div>
                <Link :href="route('invoices.show', invoice.id)" class="text-gray-500 hover:text-gray-700">← Back</Link>
            </div>

            <!-- Customer Read-Only Info -->
            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                    <div><span class="text-gray-500">Customer:</span> <div class="font-bold">{{ invoice.customer?.shop_name }}</div></div>
                    <div><span class="text-gray-500">Code:</span> <div class="font-medium">{{ invoice.customer?.customer_code }}</div></div>
                    <div><span class="text-gray-500">VAN:</span> <div class="font-medium">{{ invoice.van?.code }}</div></div>
                    <div><span class="text-gray-500">Order Booker:</span> <div class="font-medium">{{ invoice.order_booker?.name }}</div></div>
                    <div><span class="text-gray-500">Distribution:</span> <div class="font-medium">{{ invoice.distribution?.name }}</div></div>
                </div>
            </div>

             <form @submit.prevent="submit" class="space-y-6">
                <!-- Settings -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="Invoice Type" />
                            <select v-model="form.invoice_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="sale">Sale</option>
                                <option value="damage">Damage (Zero Value)</option>
                                <option value="shelf_rent">Shelf Rent</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input type="checkbox" v-model="form.is_credit" id="is_credit" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="is_credit" class="text-sm text-gray-700">Credit Sale</label>
                        </div>
                        <div class="md:col-span-2">
                             <InputLabel value="Notes" />
                             <TextInput v-model="form.notes" class="mt-1 w-full" />
                        </div>
                    </div>
                </div>

                <!-- Product Entry -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Add Products</h2>
                    <!-- Row 1 -->
                    <div class="grid grid-cols-6 lg:grid-cols-12 gap-3 items-end mb-4">
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Product Code" />
                            <TextInput ref="productCodeRef" v-model="productCode" class="mt-1 w-full" @keyup.enter="searchProductByCode" placeholder="Enter code" />
                        </div>
                        <div class="col-span-4 lg:col-span-3">
                            <SearchableSelect v-model="newItem.product_id" label="Product" :options="productOptions" option-value="id" option-label="displayLabel" placeholder="Search..." />
                        </div>
                        <div class="col-span-3 lg:col-span-2">
                             <InputLabel value="Batch / Stock" />
                             <SearchableSelect v-model="newItem.stock_id" :options="availableBatches" option-value="id" option-label="label" placeholder="Select Batch" :disabled="!newItem.product_id" class="mt-1" />
                             <div v-if="newItem.product_id && availableBatches.length===0" class="text-xs text-red-500 mt-1">No Stock</div>
                             <div v-if="newItem.available_qty > 0" class="text-xs text-emerald-600 mt-1">Avail: {{ newItem.available_qty }}</div>
                        </div>
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Cartons" />
                            <TextInput ref="cartonsRef" v-model.number="newItem.cartons" type="number" min="0" class="mt-1 w-full text-center" />
                        </div>
                        <div class="col-span-2 lg:col-span-1">
                            <InputLabel value="Pieces" />
                            <TextInput ref="piecesRef" v-model.number="newItem.pieces" type="number" min="0" class="mt-1 w-full text-center" />
                        </div>
                         <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Total Pcs" />
                            <TextInput :value="newItem.total_pieces" type="number" class="mt-1 w-full bg-gray-100 text-center font-bold" readonly />
                        </div>
                    </div>
                    
                    <!-- Row 2 (Details) -->
                    <div v-if="newItem.product_id" class="bg-gray-50 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-6 lg:grid-cols-10 gap-3 items-end">
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Excl. Price" class="text-xs" />
                                <TextInput v-model.number="newItem.exclusive_price" type="number" step="0.01" class="mt-1 w-full text-center text-sm" @input="calculateNetUnitPrice" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="FED %" class="text-xs" />
                                <TextInput v-model.number="newItem.fed_percent" type="number" class="mt-1 w-full text-center text-sm" @input="calculateNetUnitPrice" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="S.Tax %" class="text-xs" />
                                <TextInput v-model.number="newItem.sales_tax_percent" type="number" class="mt-1 w-full text-center text-sm" @input="calculateNetUnitPrice" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Ex.Tax %" class="text-xs" />
                                <TextInput v-model.number="newItem.extra_tax_percent" type="number" class="mt-1 w-full text-center text-sm" @input="calculateNetUnitPrice" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Net Price" class="text-xs font-bold text-indigo-600" />
                                <TextInput :value="formatAmount(newItem.net_unit_price)" class="mt-1 w-full bg-indigo-50 font-bold text-indigo-700 text-center text-sm" readonly />
                            </div>
                             <div class="col-span-3 lg:col-span-2">
                                <InputLabel value="Scheme" class="text-xs" />
                                <select v-model="newItem.scheme_id" @change="applyDiscountScheme(discountSchemes.find(s => s.id == newItem.scheme_id))" class="mt-1 block w-full rounded-md border-gray-300 text-sm h-[42px]">
                                    <option value="">No Scheme</option>
                                    <option v-for="s in discountSchemes" :key="s.id" :value="s.id">
                                        {{ s.name }} ({{ s.discount_type === 'percentage' ? s.discount_value+'%' : (s.discount_type === 'amount_less' ? 'Rs '+s.amount_less : 'FREE') }})
                                    </option>
                                </select>
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Disc %" class="text-xs" />
                                <TextInput v-model.number="newItem.manual_discount_percent" type="number" class="mt-1 w-full text-center text-sm" />
                            </div>
                             <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Disc Rs" class="text-xs" />
                                <TextInput v-model.number="newItem.manual_discount_amount" type="number" class="mt-1 w-full text-center text-sm" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                 <InputLabel value=" " class="invisible" />
                                 <button type="button" @click="addItem" :disabled="newItem.total_pieces<=0" class="w-full mt-1 px-3 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                     + Add
                                 </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                     <div class="p-4 border-b bg-gray-50"><h2 class="text-lg font-medium">Items ({{ form.items.length }})</h2></div>
                     <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-[10px] uppercase font-semibold text-gray-500">
                                <tr>
                                    <th class="px-2 py-3">Product</th>
                                    <th class="px-2 py-3 text-right">Total</th>
                                    <th class="px-2 py-3 text-right">Rate</th>
                                    <th class="px-2 py-3 text-right">Gross</th>
                                    <th class="px-2 py-3 text-right">Disc.</th>
                                    <th class="px-2 py-3 text-right">Net</th>
                                    <th class="px-2 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in form.items" :key="index" :class="item.is_free ? 'bg-green-50' : ''">
                                    <td class="px-2 py-3">
                                        <div class="font-medium">{{ item.product_name }} <span v-if="item.is_free" class="text-green-600 font-bold">(FREE)</span></div>
                                        <div class="text-[10px] text-gray-500">{{ item.product_code }}</div>
                                    </td>
                                    <td class="px-2 py-3 text-right">{{ item.total_pieces }}</td>
                                    <td class="px-2 py-3 text-right">{{ formatAmount(item.exclusive_price) }}</td>
                                    <td class="px-2 py-3 text-right">{{ formatAmount(item.gross_amount) }}</td>
                                    <td class="px-2 py-3 text-right text-red-500">-{{ formatAmount(getItemDiscount(item)) }}</td>
                                    <td class="px-2 py-3 text-right font-bold">
                                        {{ formatAmount(item.gross_amount - getItemDiscount(item) - (item.trade_discount_amount || 0)) }}
                                    </td>
                                    <td class="px-2 py-3 text-right">
                                        <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700">Remove</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                     </div>

                     <!-- Footer Totals -->
                     <div v-if="form.items.length > 0" class="border-t bg-gray-50 p-4">
                         <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                             <div class="bg-white p-3 rounded border"><div class="text-xs text-gray-500">Total Exclusive</div><div class="font-bold">{{ formatAmount(totalExclusive) }}</div></div>
                             <div class="bg-white p-3 rounded border"><div class="text-xs text-gray-500">Total Sales Tax</div><div class="font-bold">{{ formatAmount(totalSalesTax) }}</div></div>
                             <div class="bg-white p-3 rounded border"><div class="text-xs text-gray-500">Total Discount</div><div class="font-bold text-red-600">-{{ formatAmount(totalDiscount) }}</div></div>
                             <div class="bg-emerald-100 p-3 rounded border border-emerald-300"><div class="text-xs text-emerald-700 font-bold">Grand Total</div><div class="font-bold text-xl text-emerald-800">{{ formatAmount(grandTotal) }}</div></div>
                         </div>
                     </div>
                </div>

                <div class="flex justify-end gap-3">
                     <Link :href="route('invoices.show', invoice.id)"><SecondaryButton type="button">Cancel</SecondaryButton></Link>
                     <PrimaryButton :disabled="form.processing || form.items.length===0">Update Invoice</PrimaryButton>
                </div>
             </form>
        </div>
    </DashboardLayout>
</template>
