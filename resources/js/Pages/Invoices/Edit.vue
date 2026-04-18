<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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
    vans: Array,
    orderBookers: Array,
    products: Array,
    schemes: Array,
    distributions: Array,
    availableStocks: { type: Array, default: () => [] }
});

const page = usePage();
const currentDistribution = computed(() => page.props.currentDistribution);

// Cascading dropdown state
const filteredBookers = ref([]);
const filteredCustomers = ref([]);
const selectedCustomer = ref(props.invoice.customer || null);
const customerCode = ref('');
const orderDay = ref('');

// Product entry state
const productCode = ref('');
const selectedProduct = ref(null);

// Template refs for focus management
const productCodeRef = ref(null);
const cartonsRef = ref(null);
const piecesRef = ref(null);
const schemeRef = ref(null);

const editingIndex = ref(null);

const newItem = ref({
    product_id: '',
    stock_id: '',            // Selected batch/stock
    cartons: 0,
    pieces: 0,
    total_pieces: 0,
    exclusive_price: 0,      // Price before tax (list_price_before_tax)
    fed_percent: 0,          // FED %
    sales_tax_percent: 0,    // Sales Tax %
    extra_tax_percent: 0,    // Extra Tax % (from product type)
    adv_tax_percent: 0,      // Advance Tax % (from customer)
    net_unit_price: 0,       // Price with taxes
    scheme_id: '',
    scheme_discount: 0,
    discount_scheme_id: '',  // DiscountScheme (quantity-based)
    free_product: null,      // Free product from scheme
    free_pieces: 0,          // Free pieces quantity
    manual_discount_percentage: 0,  // Manual discount %
    manual_discount_amount: 0,   // Manual discount amount (Rs)
    batch_number: '',        // Selected batch number
    available_qty: 0,        // Available quantity in selected batch
    trade_discount_percent: 0, // Trade discount (retail margin) from product
    is_net_fixed: false      // Flag to keep Net Price fixed during tax changes
});
const productSchemes = ref([]);
const discountSchemes = ref([]);

const form = useForm({
    invoice_type: props.invoice.invoice_type,
    is_credit: Boolean(props.invoice.is_credit),
    notes: props.invoice.notes || '',
    distribution_id: props.invoice.distribution_id,
    van_id: props.invoice.van_id,
    order_booker_id: props.invoice.order_booker_id,
    customer_id: props.invoice.customer_id,
    invoice_date: props.invoice.invoice_date,
    items: props.invoice.items.map(item => {
        const product = item.product || {};
        const brand = product.brand || {};
        
        // Manual discount and scheme discount info
        const scheme_discount = parseFloat(item.scheme_discount_amount) || parseFloat(item.scheme_discount) || 0;
        const total_discount = parseFloat(item.discount) || 0;
        const manual_discount_amount = parseFloat(item.manual_discount_amount) || (total_discount - scheme_discount);
        
        return {
            id: item.id,
            product_id: item.product_id,
            product_name: product.name,
            product_code: product.dms_code || product.sku,
            brand_id: product.brand_id,
            brand_name: brand.name,
            stock_id: item.stock_id,
            batch_number: item.batch_number,
            cartons: parseInt(item.cartons),
            pieces: parseInt(item.pieces),
            total_pieces: parseInt(item.total_pieces),
            exclusive_price: parseFloat(item.list_price_before_tax) || (parseFloat(item.exclusive_amount) / parseInt(item.total_pieces)),
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
            price: parseFloat(item.price),
            scheme_id: item.scheme_id,
            scheme_name: item.scheme?.name || (item.is_free ? 'FREE' : null),
            scheme_discount: scheme_discount,
            discount_scheme_id: item.scheme_id,
            manual_discount_percentage: parseFloat(item.manual_discount_percentage) || 0,
            manual_discount_amount: manual_discount_amount,
            total_discount: total_discount,
            trade_discount_amount: parseFloat(item.retail_margin),
            trade_discount_percent: parseFloat(item.trade_discount_percent) || 0,
            is_free: Boolean(item.is_free),
            is_food: (product.product_type?.name || '').toLowerCase().trim() === 'food',
            brand_scheme_brand_id: null // Will be handled if brands change
        };
    })
});

// Day options
const dayOptions = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Brand quantities for schemes
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

// Van options with distribution label
const vanOptions = computed(() => {
    return props.vans.map(van => ({
        ...van,
        displayLabel: !currentDistribution.value?.id && van.distribution
            ? `${van.code} (${van.distribution.name})`
            : van.code
    }));
});

// Product options
const productOptions = computed(() => {
    return props.products.map(p => ({
        ...p,
        displayLabel: `${p.dms_code || p.sku || ''} - ${p.name}`
    }));
});

// Get available batches/stocks for the selected product
const availableBatches = computed(() => {
    if (!newItem.value.product_id) return [];

    let stocks = props.availableStocks.filter(s => Number(s.product_id) === Number(newItem.value.product_id));

    // Filter by selected distribution
    const distId = form.distribution_id || currentDistribution.value?.id;
    if (distId) {
        stocks = stocks.filter(s => Number(s.distribution_id) === Number(distId));
    }

    return stocks.map(s => ({
        ...s,
        label: `${s.batch_number ? 'Batch: ' + s.batch_number : 'No Batch'} ${s.location ? '(' + s.location + ')' : ''} - Qty: ${s.quantity}`,
    }));
});

// Load Van Details (Bookers + Customers)
const loadVanDetails = async (vanId, preserveSelection = false) => {
    if (!vanId) {
        filteredBookers.value = [];
        filteredCustomers.value = [];
        return;
    }

    try {
        // Get order bookers for van
        const bookersResponse = await axios.get(route('api.bookers-by-van', vanId));
        filteredBookers.value = bookersResponse.data;

        // Handle auto-selection or preservation
        if (preserveSelection && form.order_booker_id) {
            const isValid = filteredBookers.value.some(b => b.id == form.order_booker_id);
            if (!isValid) form.order_booker_id = '';
        } else {
            if (filteredBookers.value.length === 1) {
                form.order_booker_id = filteredBookers.value[0].id;
            } else {
                form.order_booker_id = '';
            }
        }

        // Load customers by van code
        const van = props.vans.find(v => v.id === parseInt(vanId));
        if (van) {
            const customersResponse = await axios.get(route('api.customers-by-van', van.code), {
                params: { day: orderDay.value || undefined }
            });
            filteredCustomers.value = customersResponse.data;
        }
    } catch (e) {
        filteredBookers.value = [];
        filteredCustomers.value = [];
    }
};

// Pricing Helpers
const calculateNetUnitPrice = () => {
    const exclusive = parseFloat(newItem.value.exclusive_price) || 0;
    const fed = parseFloat(newItem.value.fed_percent) / 100 || 0;
    const salesTax = parseFloat(newItem.value.sales_tax_percent) / 100 || 0;
    const extraTax = parseFloat(newItem.value.extra_tax_percent) / 100 || 0;
    const productTypeName = (selectedProduct.value?.product_type?.name || '').toLowerCase().trim();
    const isFood = productTypeName === 'food';

    newItem.value.net_unit_price = isFood 
        ? exclusive * (1 + salesTax + extraTax) 
        : exclusive * (1 + fed) * (1 + salesTax);
};

const calculateReversePrice = () => {
    const netPrice = parseFloat(newItem.value.net_unit_price) || 0;
    const fed = parseFloat(newItem.value.fed_percent) / 100 || 0;
    const salesTax = parseFloat(newItem.value.sales_tax_percent) / 100 || 0;
    const extraTax = parseFloat(newItem.value.extra_tax_percent) / 100 || 0;
    const productTypeName = (selectedProduct.value?.product_type?.name || '').toLowerCase().trim();
    const isFood = productTypeName === 'food';

    const divisor = isFood ? (1 + salesTax + extraTax) : (1 + fed) * (1 + salesTax);
    newItem.value.exclusive_price = (divisor === 0 || divisor === 1) ? netPrice : netPrice / divisor;
};

const handleTaxChange = () => newItem.value.is_net_fixed ? calculateReversePrice() : calculateNetUnitPrice();
const onExclusivePriceInput = () => { newItem.value.is_net_fixed = false; calculateNetUnitPrice(); };

// Watchers
watch(() => form.van_id, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        // Only clear if changed after mount
        if (oldValue !== undefined) {
            form.customer_id = '';
            selectedCustomer.value = null;
        }
        loadVanDetails(newValue, true);
    }
});

watch(orderDay, async (day) => {
    if (form.van_id) {
        try {
            const van = props.vans.find(v => v.id === parseInt(form.van_id));
            if (van) {
                const response = await axios.get(route('api.customers-by-van', van.code), {
                    params: { day: day || undefined }
                });
                filteredCustomers.value = response.data;
            }
        } catch (e) { filteredCustomers.value = []; }
    }
});

watch(() => form.customer_id, (customerId) => {
    if (customerId) {
        selectedCustomer.value = filteredCustomers.value.find(c => c.id === parseInt(customerId));
    } else {
        selectedCustomer.value = null;
    }
});

// Initialize on mount
onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    if (form.van_id) {
        loadVanDetails(form.van_id, true);
    }
});

const loadDiscountSchemes = async (productId, quantity, brandId = null) => {
    try {
        let bq = quantity;
        if (brandId) {
            bq = (brandQuantities.value[brandId] || 0) + (editingIndex.value !== null ? 0 : quantity);
            // If editing, brandQuantities already includes this item, so we don't add quantity twice
            // Actually, brandQuantities is computed from form.items. 
            // If we are editing, we are modifying one of those items.
        }
        
        const response = await axios.get(route('api.discount-schemes', productId), {
            params: {
                quantity,
                brand_quantity: bq,
                customer_id: form.customer_id,
                sub_distribution_id: selectedCustomer.value?.sub_distribution_id
            }
        });
        discountSchemes.value = response.data;
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
        newItem.value.free_pieces = 0;
    } else if (scheme.discount_type === 'free_product') {
        newItem.value.scheme_discount = 0;
        newItem.value.free_product = scheme.free_product;
        newItem.value.free_pieces = scheme.free_pieces || 0;
    }
};

watch(() => newItem.value.product_id, (v) => {
    if (v) {
        const p = props.products.find(x => x.id === parseInt(v));
        if (p) {
            selectedProduct.value = p;
            
            // Only reset if not in edit mode or if product changed
            if (editingIndex.value === null) {
                newItem.value.stock_id = '';
                newItem.value.batch_number = '';
                newItem.value.available_qty = 0;
                newItem.value.cartons = 0;
                newItem.value.pieces = 0;
                newItem.value.total_pieces = 0;
            }

            newItem.value.exclusive_price = parseFloat(p.list_price_before_tax) || 0;
            newItem.value.fed_percent = parseFloat(p.fed_percent) || 0;
            newItem.value.sales_tax_percent = parseFloat(p.fed_sales_tax) || 0;
            newItem.value.extra_tax_percent = parseFloat(p.product_type?.extra_tax) || 0;
            newItem.value.trade_discount_percent = parseFloat(p.retail_margin) || 0;
            newItem.value.adv_tax_percent = parseFloat(selectedCustomer.value?.adv_tax_percent) || 0;

            const productTypeName = (p.product_type?.name || '').toLowerCase().trim();
            if (productTypeName === 'food') {
                newItem.value.is_net_fixed = false;
                calculateNetUnitPrice();
            } else {
                newItem.value.net_unit_price = parseFloat(p.unit_price) || 0;
                newItem.value.is_net_fixed = true;
                calculateReversePrice();
            }

            productCode.value = p.dms_code || p.sku || '';

            // Auto-select first batch if not editing
            if (editingIndex.value === null) {
                const stocks = availableBatches.value;
                if (stocks.length > 0) {
                    newItem.value.stock_id = stocks[0].id;
                }
            }
        }
    } else {
        selectedProduct.value = null;
        discountSchemes.value = [];
    }
});

watch(() => newItem.value.stock_id, (v) => {
    const s = availableBatches.value.find(x => x.id === v);
    if (s) {
        newItem.value.batch_number = s.batch_number || '';
        newItem.value.available_qty = s.quantity || 0;
    }
});

watch([() => newItem.value.cartons, () => newItem.value.pieces], () => {
    const packingSize = selectedProduct.value?.pieces_per_packing || selectedProduct.value?.packing?.quantity || 12;
    newItem.value.total_pieces = (newItem.value.cartons * packingSize) + newItem.value.pieces;
    
    if (newItem.value.product_id && newItem.value.total_pieces > 0) {
        loadDiscountSchemes(newItem.value.product_id, newItem.value.total_pieces, selectedProduct.value?.brand_id);
    }
});

const editItem = (index) => {
    const item = form.items[index];
    if (item.is_free) return; // Free items managed via parents

    editingIndex.value = index;
    
    // Reset newItem first to avoid watcher side-effects
    newItem.value.product_id = item.product_id;
    
    // Wait for the next tick for the product watcher to fire, then override values
    setTimeout(() => {
        newItem.value.cartons = item.cartons;
        newItem.value.pieces = item.pieces;
        newItem.value.total_pieces = item.total_pieces;
        newItem.value.stock_id = item.stock_id;
        newItem.value.batch_number = item.batch_number;
        newItem.value.exclusive_price = item.exclusive_price;
        newItem.value.net_unit_price = item.net_unit_price;
        newItem.value.is_net_fixed = true;
        
        // Find existing scheme
        if (item.scheme_id) {
            newItem.value.discount_scheme_id = item.scheme_id;
            newItem.value.scheme_discount = item.scheme_discount;
        }

        newItem.value.manual_discount_percentage = item.manual_discount_percentage || 0;

        setTimeout(() => cartonsRef.value?.focus(), 50);
    }, 50);
};

const cancelEdit = () => {
    editingIndex.value = null;
    resetNewItem();
};

const addItem = () => {
    if (!newItem.value.product_id || newItem.value.total_pieces <= 0) return;

    const p = selectedProduct.value;

    // Check for duplicates (excluding current item being edited)
    const duplicate = form.items.find((item, idx) => 
        item.product_id === parseInt(newItem.value.product_id) && 
        !item.is_free && 
        idx !== editingIndex.value
    );

    if (duplicate) {
        Swal.fire({
            icon: 'error',
            title: 'Product Already Added',
            text: `"${p?.name}" is already in the invoice.`,
            confirmButtonColor: '#059669',
        });
        return;
    }

    // Calculations
    const exclAmt = newItem.value.exclusive_price * newItem.value.total_pieces;
    const fedAmt = exclAmt * (newItem.value.fed_percent / 100);
    const productTypeName = (p.product_type?.name || '').toLowerCase().trim();
    const isFood = productTypeName === 'food';
    const sTaxAmt = isFood 
        ? exclAmt * (newItem.value.sales_tax_percent / 100)
        : (exclAmt + fedAmt) * (newItem.value.sales_tax_percent / 100);
    
    const xTaxAmt = exclAmt * ((newItem.value.extra_tax_percent || 0) / 100);
    const grossAmt = exclAmt + fedAmt + sTaxAmt + xTaxAmt;
    
    const tradeDisAmt = (grossAmt / (1 + (newItem.value.trade_discount_percent / 100))) * (newItem.value.trade_discount_percent / 100);
    const manualDisAmt = exclAmt * (newItem.value.manual_discount_percentage / 100) + newItem.value.manual_discount_amount;
    const totalDisAmt = (newItem.value.scheme_discount || 0) + manualDisAmt;
    
    const taxableForAdv = grossAmt - tradeDisAmt - totalDisAmt;
    const advTaxAmt = taxableForAdv * (newItem.value.adv_tax_percent / 100);

    const itemData = {
        id: editingIndex.value !== null ? form.items[editingIndex.value].id : null,
        product_id: p.id,
        product_name: p.name,
        product_code: p.dms_code || p.sku,
        brand_id: p.brand_id,
        brand_name: p.brand?.name,
        stock_id: newItem.value.stock_id,
        batch_number: newItem.value.batch_number,
        cartons: newItem.value.cartons,
        pieces: newItem.value.pieces,
        total_pieces: newItem.value.total_pieces,
        exclusive_price: newItem.value.exclusive_price,
        fed_percent: newItem.value.fed_percent,
        fed_amount: fedAmt,
        sales_tax_percent: newItem.value.sales_tax_percent,
        sales_tax_amount: sTaxAmt,
        extra_tax_percent: newItem.value.extra_tax_percent,
        extra_tax_amount: xTaxAmt,
        adv_tax_percent: newItem.value.adv_tax_percent,
        adv_tax_amount: advTaxAmt,
        gross_amount: grossAmt,
        net_unit_price: newItem.value.net_unit_price,
        scheme_id: newItem.value.scheme_id,
        scheme_name: discountSchemes.value.find(s => s.id == newItem.value.scheme_id)?.name,
        scheme_discount: newItem.value.scheme_discount,
        discount_scheme_id: newItem.value.discount_scheme_id,
        free_product: newItem.value.free_product,
        free_pieces: newItem.value.free_pieces,
        manual_discount_percentage: newItem.value.manual_discount_percentage,
        manual_discount_amount: newItem.value.manual_discount_amount,
        total_discount: totalDisAmt,
        trade_discount_percent: newItem.value.trade_discount_percent,
        trade_discount_amount: tradeDisAmt,
        is_free: false,
        is_food: isFood
    };

    if (editingIndex.value !== null) {
        form.items[editingIndex.value] = itemData;
        editingIndex.value = null;
    } else {
        form.items.push(itemData);
    }

    // Free items
    if (newItem.value.free_product && newItem.value.free_pieces > 0) {
        addFreeItem(newItem.value.free_product, newItem.value.free_pieces);
    }

    // Sort: items first, then free products
    form.items.sort((a, b) => (a.is_free === b.is_free) ? 0 : (a.is_free ? 1 : -1));

    resetNewItem();
};

const addFreeItem = (fp, qty) => {
    const exclPrice = parseFloat(fp.list_price_before_tax) || 0;
    const fedP = parseFloat(fp.fed_percent) || 0;
    const sTaxP = parseFloat(fp.fed_sales_tax) || 0;
    const xTaxP = parseFloat(fp.product_type?.extra_tax) || 0;

    const exclAmt = exclPrice * qty;
    const fdAmt = exclAmt * fedP / 100;
    const stAmt = (exclAmt + fdAmt) * sTaxP / 100;
    const xtAmt = exclAmt * xTaxP / 100;
    const gross = exclAmt + fdAmt + stAmt + xtAmt;

    form.items.push({
        id: null,
        product_id: fp.id,
        product_name: fp.name + ' (FREE)',
        product_code: fp.dms_code,
        cartons: 0,
        pieces: qty,
        total_pieces: qty,
        exclusive_price: exclPrice,
        fed_percent: fedP,
        fed_amount: fdAmt,
        sales_tax_percent: sTaxP,
        sales_tax_amount: stAmt,
        extra_tax_percent: xTaxP,
        extra_tax_amount: xtAmt,
        adv_tax_percent: 0,
        adv_tax_amount: 0,
        gross_amount: gross,
        net_unit_price: exclPrice * (1 + fedP / 100) * (1 + sTaxP / 100),
        scheme_name: 'FREE',
        total_discount: 0,
        trade_discount_amount: gross,
        is_free: true
    });
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
        manual_discount_percentage: 0,
        manual_discount_amount: 0,
        batch_number: '',
        available_qty: 0,
        trade_discount_percent: 0,
        is_net_fixed: false
    };
    selectedProduct.value = null;
    productCode.value = '';
    discountSchemes.value = [];
    setTimeout(() => productCodeRef.value?.focus(), 100);
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const searchProductByCode = () => {
    const code = productCode.value.toLowerCase().trim();
    if (!code) return;
    const product = props.products.find(p => 
        (p.dms_code?.toLowerCase() === code) || (p.sku?.toLowerCase() === code)
    );
    if (product) {
        newItem.value.product_id = product.id;
    }
};

const searchCustomerByCode = async () => {
    if (!customerCode.value) return;
    try {
        const response = await axios.get(route('api.customer-by-code', customerCode.value));
        const customer = response.data;
        if (customer) {
            // Find the van for this customer
            const van = props.vans.find(v => v.code === customer.van);
            if (van) {
                form.van_id = van.id;
                // Wait for van details to load then select customer
                await loadVanDetails(van.id, true);
                form.customer_id = customer.id;
            }
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Not Found', text: 'Customer code not found.' });
    }
};

const getItemExclusive = (item) => parseFloat(item.exclusive_price) * parseInt(item.total_pieces);
const getItemFed = (item) => getItemExclusive(item) * (parseFloat(item.fed_percent) / 100);
const getItemSalesTax = (item) => {
    const excl = getItemExclusive(item);
    return item.is_food 
        ? excl * (parseFloat(item.sales_tax_percent) / 100)
        : (excl + getItemFed(item)) * (parseFloat(item.sales_tax_percent) / 100);
};
const getItemExtraTax = (item) => getItemExclusive(item) * ((parseFloat(item.extra_tax_percent) || 0) / 100);
const getItemAdvTax = (item) => parseFloat(item.adv_tax_amount) || 0;
const getItemDiscount = (item) => getItemSchemeDiscount(item) + getItemManualDiscount(item);
const getItemSchemeDiscount = (item) => parseFloat(item.scheme_discount) || 0;
const getItemManualDiscount = (item) => Math.max(0, (parseFloat(item.total_discount) || 0) - getItemSchemeDiscount(item));

const totalExclusive = computed(() => form.items.reduce((sum, item) => sum + getItemExclusive(item), 0));
const totalFed = computed(() => form.items.reduce((sum, item) => sum + getItemFed(item), 0));
const totalSalesTax = computed(() => form.items.reduce((sum, item) => sum + getItemSalesTax(item), 0));
const totalExtraTax = computed(() => form.items.reduce((sum, item) => sum + getItemExtraTax(item), 0));
const totalAdvTax = computed(() => form.items.reduce((sum, item) => sum + getItemAdvTax(item), 0));
const totalGrossAmount = computed(() => form.items.reduce((sum, item) => sum + parseFloat(item.gross_amount), 0));
const totalDiscount = computed(() => form.items.reduce((sum, item) => sum + getItemDiscount(item), 0));
const totalSchemeDiscount = computed(() => form.items.reduce((sum, item) => sum + getItemSchemeDiscount(item), 0));
const totalManualDiscount = computed(() => form.items.reduce((sum, item) => sum + getItemManualDiscount(item), 0));
const totalTradeDiscount = computed(() => form.items.reduce((sum, item) => sum + (parseFloat(item.trade_discount_amount) || 0), 0));
const grandTotal = computed(() => totalGrossAmount.value - totalDiscount.value - totalTradeDiscount.value + totalAdvTax.value);

const formatAmount = (amount) => new Intl.NumberFormat('en-PK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 5
}).format(amount || 0);

const handleKeydown = (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        submit();
    }
};

onUnmounted(() => document.removeEventListener('keydown', handleKeydown));

const submit = () => {
    if (form.items.length === 0) {
        Swal.fire({ icon: 'error', title: 'Empty Invoice', text: 'Please add at least one item.' });
        return;
    }
    form.put(route('invoices.update', props.invoice.id));
};
</script>

<template>

    <Head title="Edit Invoice" />
    <DashboardLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Invoice #{{ invoice.invoice_number }}</h1>
                    <p class="text-gray-500 mt-1">Modify invoice. Press F2 to save.</p>
                </div>
                <Link :href="route('invoices.show', invoice.id)" class="text-gray-500 hover:text-gray-700">← Back</Link>
            </div>
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Invoice Details (Read-Only Context) -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Invoice Details</h2>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="Distribution" />
                            <SearchableSelect v-model="form.distribution_id" :options="distributions" option-value="id" option-label="name" placeholder="Select Distribution" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="VAN" />
                            <SearchableSelect v-model="form.van_id" :options="vanOptions" option-value="id" option-label="displayLabel" placeholder="Select Van" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Order Booker" />
                            <SearchableSelect v-model="form.order_booker_id" :options="filteredBookers" option-value="id" option-label="name" placeholder="Select Booker" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Invoice Date" />
                            <TextInput v-model="form.invoice_date" type="date" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Invoice Type" />
                            <select v-model="form.invoice_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="sale">Sale</option>
                                <option value="damage">Damage</option>
                                <option value="shelf_rent">Shelf Rent</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input type="checkbox" v-model="form.is_credit" id="is_credit" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="is_credit" class="text-sm text-gray-700">Credit Sale</label>
                        </div>
                        <div class="col-span-2">
                            <InputLabel value="Notes" />
                            <TextInput v-model="form.notes" placeholder="Optional notes" class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>

                <!-- Customer Selection -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Customer Selection</h2>
                        <div class="flex gap-2">
                            <div class="w-32">
                                <InputLabel value="Filter Day" class="sr-only" />
                                <select v-model="orderDay" class="w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">All Days</option>
                                    <option v-for="day in dayOptions" :key="day" :value="day">{{ day }}</option>
                                </select>
                            </div>
                            <div class="w-40 flex">
                                <TextInput v-model="customerCode" placeholder="Code (Enter)" class="!rounded-r-none !border-r-0 text-xs w-full" @keyup.enter="searchCustomerByCode" />
                                <button type="button" @click="searchCustomerByCode" class="px-2 bg-gray-100 border border-gray-300 rounded-r-md hover:bg-gray-200">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <div class="col-span-1">
                            <SearchableSelect v-model="form.customer_id" :options="filteredCustomers" option-value="id" option-label="shop_name" placeholder="Search customer..." :disabled="!form.van_id" />
                            <p v-if="!form.van_id" class="mt-1 text-xs text-amber-600">Please select a Van first</p>
                            <p v-else-if="filteredCustomers.length === 0" class="mt-1 text-xs text-gray-500">No customers found for this van/day</p>
                        </div>

                        <div v-if="selectedCustomer" class="p-4 bg-emerald-50 rounded-xl border border-emerald-200">
                            <div class="grid grid-cols-2 gap-y-2 text-sm">
                                <div><span class="text-gray-500">Code:</span> <span class="font-medium ml-1">{{ selectedCustomer.customer_code }}</span></div>
                                <div><span class="text-gray-500">Shop:</span> <span class="font-medium ml-1 text-emerald-800">{{ selectedCustomer.shop_name }}</span></div>
                                <div><span class="text-gray-500">ATL:</span> <span class="font-medium ml-1" :class="selectedCustomer.atl ? 'text-green-600' : 'text-red-600'">{{ selectedCustomer.atl ? 'Yes' : 'No' }}</span></div>
                                <div><span class="text-gray-500">Adv Tax:</span> <span class="font-medium ml-1">{{ selectedCustomer.adv_tax_percent }}%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100" :class="{ 'ring-2 ring-orange-500 bg-orange-50/10': editingIndex !== null }">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">{{ editingIndex !== null ? 'Update Product' : 'Add Products' }}</h2>
                        <span v-if="editingIndex !== null" class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-bold rounded uppercase">Editing Row #{{ editingIndex + 1 }}</span>
                    </div>

                    <div class="grid grid-cols-6 lg:grid-cols-12 gap-3 items-end mb-4">
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Product Code" />
                            <div class="flex items-stretch mt-1">
                                <TextInput ref="productCodeRef" v-model="productCode" placeholder="Code" class="flex-1 !rounded-r-none !border-r-0 min-w-0" @keyup.enter="searchProductByCode" />
                                <button type="button" @click="searchProductByCode" class="px-3 bg-emerald-600 text-white rounded-r-md hover:bg-emerald-700 border border-emerald-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </button>
                            </div>
                        </div>
                        <div class="col-span-4 lg:col-span-3">
                            <SearchableSelect v-model="newItem.product_id" label="Product" :options="productOptions" option-value="id" option-label="displayLabel" placeholder="Search product..." />
                        </div>
                        <div class="col-span-3 lg:col-span-2">
                            <InputLabel value="Batch / Stock" />
                            <SearchableSelect v-model="newItem.stock_id" :options="availableBatches" option-value="id" option-label="label" placeholder="Select Batch" :disabled="!newItem.product_id" class="mt-1" />
                            <div v-if="newItem.stock_id && newItem.available_qty > 0" class="text-xs text-emerald-600 mt-1 font-medium">Available: {{ newItem.available_qty }} pcs</div>
                        </div>
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Cartons" />
                            <TextInput ref="cartonsRef" v-model.number="newItem.cartons" type="number" min="0" class="mt-1 w-full text-center font-medium" @keydown.enter.prevent="piecesRef?.focus()" />
                        </div>
                        <div class="col-span-2 lg:col-span-1">
                            <InputLabel value="Pieces" />
                            <TextInput ref="piecesRef" v-model.number="newItem.pieces" type="number" min="0" class="mt-1 w-full text-center font-medium" @keydown.enter.prevent="schemeRef?.focus()" />
                        </div>
                        <div class="col-span-2 lg:col-span-2">
                            <InputLabel value="Total Pcs" />
                            <TextInput :value="newItem.total_pieces" type="number" class="mt-1 w-full text-center font-bold bg-emerald-50 text-emerald-700" readonly />
                        </div>
                    </div>

                    <div v-if="newItem.product_id" class="bg-gray-50 p-4 rounded-xl mb-4 border border-gray-100">
                        <div class="grid grid-cols-6 lg:grid-cols-10 gap-3 items-end">
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Excl. Price" class="text-[10px] uppercase text-gray-400" />
                                <TextInput v-model.number="newItem.exclusive_price" type="number" step="0.00001" class="mt-1 w-full text-sm text-center" @input="onExclusivePriceInput" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="FED %" class="text-[10px] uppercase text-gray-400" />
                                <TextInput v-model.number="newItem.fed_percent" type="number" step="0.00001" class="mt-1 w-full text-sm text-center" @input="handleTaxChange" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="S.Tax %" class="text-[10px] uppercase text-gray-400" />
                                <TextInput v-model.number="newItem.sales_tax_percent" type="number" step="0.00001" class="mt-1 w-full text-sm text-center" @input="handleTaxChange" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Extra Tax %" class="text-[10px] uppercase text-gray-400" />
                                <TextInput v-model.number="newItem.extra_tax_percent" type="number" step="0.00001" class="mt-1 w-full text-sm text-center" @input="handleTaxChange" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Adv.Tax %" class="text-[10px] uppercase text-gray-400" />
                                <TextInput v-model.number="newItem.adv_tax_percent" type="number" step="0.00001" class="mt-1 w-full text-sm text-center" @input="handleTaxChange" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Net Price" class="text-[10px] uppercase font-bold text-indigo-600" />
                                <TextInput v-model.number="newItem.net_unit_price" type="number" step="0.00001" class="mt-1 w-full bg-indigo-50 font-bold text-indigo-700 text-sm text-center" @input="handleTaxChange" @focus="newItem.is_net_fixed = true" />
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Trade %" class="text-[10px] uppercase text-gray-400" />
                                <TextInput v-model.number="newItem.trade_discount_percent" type="number" step="0.01" class="mt-1 w-full text-sm text-center bg-amber-50 text-amber-700" readonly />
                            </div>
                            <div class="col-span-3 lg:col-span-2">
                                <InputLabel value="Scheme" class="text-[10px] uppercase text-gray-400" />
                                <select ref="schemeRef" v-model="newItem.discount_scheme_id" @change="applyDiscountScheme(discountSchemes.find(s => s.id == newItem.discount_scheme_id))" @keydown.enter.prevent="addItem" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm h-[42px]" :class="newItem.free_product ? 'bg-green-50 border-green-300' : (newItem.scheme_discount > 0 ? 'bg-orange-50 border-orange-300' : '')">
                                    <option value="">No Scheme</option>
                                    <option v-for="s in discountSchemes" :key="s.id" :value="s.id">{{ s.name }} - {{ s.discount_type === 'amount_less' ? `Rs ${s.amount_less}` : `${s.free_pieces} FREE` }}</option>
                                </select>
                            </div>
                            <div class="col-span-2 lg:col-span-1">
                                <InputLabel value="Disc. %" class="text-[10px] uppercase text-gray-400" />
                                <TextInput v-model.number="newItem.manual_discount_percentage" type="number" step="0.00001" min="0" max="100" class="mt-1 w-full text-sm text-center bg-gray-50" readonly />
                            </div>
                            <div class="col-span-2 lg:col-span-1 flex gap-2">
                                <button type="button" @click="addItem" :disabled="!newItem.product_id || newItem.total_pieces <= 0" class="flex-1 mt-5 px-4 py-2.5 text-white rounded-lg disabled:opacity-50 font-medium transition-all shadow-sm" :class="editingIndex !== null ? 'bg-orange-600 hover:bg-orange-700' : 'bg-emerald-600 hover:bg-emerald-700'">
                                    {{ editingIndex !== null ? '✓ Update' : '+ Add' }}
                                </button>
                                <button v-if="editingIndex !== null" type="button" @click="cancelEdit" class="mt-5 p-2.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 border border-gray-200 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <h2 class="text-lg font-medium text-gray-900">Items ({{ form.items.length }})</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                                <tr>
                                    <th class="px-2 py-3">#</th>
                                    <th class="px-2 py-3">Product</th>
                                    <th class="px-2 py-3 text-right">Ctns</th>
                                    <th class="px-2 py-3 text-right">Pcs</th>
                                    <th class="px-2 py-3 text-right">Total</th>
                                    <th class="px-2 py-3 text-right">Rate</th>
                                    <th class="px-2 py-3 text-right">S.Tax</th>
                                    <th class="px-2 py-3 text-right text-purple-600">Extra Tax</th>
                                    <th class="px-2 py-3 text-right">Adv.Tax</th>
                                    <th class="px-2 py-3 text-right">Gross</th>
                                    <th class="px-2 py-3 text-right text-amber-600">Trade Disc.</th>
                                    <th class="px-2 py-3 text-right">Scheme Disc.</th>
                                    <th class="px-2 py-3 text-right">Cust. Disc.</th>
                                    <th class="px-2 py-3 text-right font-bold text-emerald-700">Net</th>
                                    <th class="px-2 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, index) in form.items" :key="index" :class="[item.is_free ? 'bg-emerald-50/60' : '', editingIndex === index ? 'bg-orange-50 ring-2 ring-inset ring-orange-500' : '']">
                                    <td class="px-2 py-3">{{ index + 1 }}</td>
                                    <td class="px-2 py-3">
                                        <div class="font-medium" :class="{ 'text-emerald-700': item.is_free }">
                                            {{ item.product_name }}
                                            <span v-if="item.is_free" class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase tracking-wide border border-emerald-200">FREE</span>
                                        </div>
                                        <div class="text-xs text-gray-500">{{ item.product_code }}</div>
                                    </td>
                                    <td class="px-2 py-3 text-right">{{ item.cartons }}</td>
                                    <td class="px-2 py-3 text-right">{{ item.pieces }}</td>
                                    <td class="px-2 py-3 text-right font-medium">{{ item.total_pieces }}</td>
                                    <td class="px-2 py-3 text-right text-gray-700">
                                        {{ formatAmount(item.exclusive_price) }}
                                        <div class="text-[10px] text-gray-400">Net: {{ formatAmount(item.net_unit_price) }}</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemSalesTax(item)) }}
                                        <div class="text-[10px]">({{ item.sales_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-purple-600">
                                        {{ formatAmount(getItemExtraTax(item)) }}
                                        <div class="text-[10px]">({{ item.extra_tax_percent || 0 }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right text-gray-500">
                                        {{ formatAmount(getItemAdvTax(item)) }}
                                        <div class="text-[10px]">({{ item.adv_tax_percent }}%)</div>
                                    </td>
                                    <td class="px-2 py-3 text-right font-medium text-gray-900">{{ formatAmount(item.gross_amount) }}</td>
                                    <td class="px-2 py-3 text-right text-amber-600">{{ formatAmount(item.trade_discount_amount || 0) }}</td>
                                    <td class="px-2 py-3 text-right text-orange-600">-{{ formatAmount(getItemSchemeDiscount(item)) }}</td>
                                    <td class="px-2 py-3 text-right text-red-600 font-medium">-{{ formatAmount(getItemManualDiscount(item)) }}</td>
                                    <td class="px-2 py-3 text-right font-bold text-emerald-600">
                                        {{ formatAmount(parseFloat(item.gross_amount) - getItemDiscount(item) - (parseFloat(item.trade_discount_amount) || 0) + getItemAdvTax(item)) }}
                                    </td>
                                    <td class="px-2 py-3">
                                        <div class="flex items-center gap-1">
                                            <button v-if="!item.is_free" type="button" @click="editItem(index)" class="p-1.5 text-orange-600 hover:bg-orange-100 rounded-md transition-colors" title="Edit Item">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button type="button" @click="removeItem(index)" class="p-1.5 text-red-600 hover:bg-red-100 rounded-md transition-colors" title="Remove Item">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="16" class="px-4 py-8 text-center text-gray-500">No items added. Use the
                                        form above to
                                        add products.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Invoice Summary -->
                    <div v-if="form.items.length > 0" class="border-t border-gray-200 bg-gray-50 p-4">
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Total Exclusive</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalExclusive) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Total FED</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalFed) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Total Sales Tax</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalSalesTax) }}</div>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-lg border border-purple-200">
                                <div class="text-purple-500 text-xs uppercase">Total Extra Tax</div>
                                <div class="font-bold text-lg text-purple-700">{{ formatAmount(totalExtraTax) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Total Adv. Tax</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalAdvTax) }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                            <div class="bg-white p-3 rounded-lg border">
                                <div class="text-gray-500 text-xs uppercase">Gross Amount</div>
                                <div class="font-bold text-lg">{{ formatAmount(totalGrossAmount) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-red-200">
                                <div class="text-red-500 text-xs uppercase">Scheme Discount</div>
                                <div class="font-bold text-lg text-red-600">-{{ formatAmount(totalSchemeDiscount) }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-red-200">
                                <div class="text-red-500 text-xs uppercase">Cust. Discount</div>
                                <div class="font-bold text-lg text-red-600">-{{ formatAmount(totalManualDiscount) }}</div>
                            </div>
                            <div class="bg-amber-50 p-3 rounded-lg border border-amber-200">
                                <div class="text-amber-600 text-xs uppercase">Total Trade Discount</div>
                                <div class="font-bold text-lg text-amber-700">-{{ formatAmount(totalTradeDiscount) }}
                                </div>
                            </div>
                            <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-300">
                                <div class="text-emerald-600 text-xs uppercase font-bold">Grand Total</div>
                                <div class="font-bold text-2xl text-emerald-700">Rs. {{ formatAmount(grandTotal) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('invoices.show', invoice.id)">
                        <SecondaryButton type="button">Cancel</SecondaryButton>
                    </Link>
                    <PrimaryButton :disabled="form.processing || form.items.length === 0">{{ form.processing ?
                        'Saving...' : 'Update Invoice' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
