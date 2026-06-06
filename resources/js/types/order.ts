export type OrderItem = {
    id: number;
    productId?: number;
    productName: string;
    qty: number;
    unitPrice?: number;
    subtotal?: number;
    size?: string | null;
    color?: string | null;
    imageUrl?: string | null;
};

export type Order = {
    id: number;
    orderNumber: string;
    customerName: string;
    customerPhone?: string;
    customerEmail?: string;
    shippingAddress?: string;
    fullShippingAddress?: string;
    shippingCity?: string;
    shippingCost?: number;
    totalPrice?: number;
    taxAmount?: number;
    discountAmount?: number;
    grandTotal: number;
    uniquePaymentAmount?: number | null;
    paymentMethod?: string;
    paymentStatus?: string;
    paymentConfirmationStatus?: string;
    orderStatus: string;
    bankName?: string | null;
    bankAccountNumber?: string | null;
    bankAccountName?: string | null;
    paymentDueAt?: string | null;
    paidAt?: string | null;
    deliveredAt?: string | null;
    completedAt?: string | null;
    courier?: string | null;
    courierService?: string | null;
    trackingNumber?: string | null;
    notes?: string | null;
    createdAt?: string;
    items?: OrderItem[];
    isReplacement?: boolean;
};

export type OrderSummary = Pick<
    Order,
    'id' | 'orderNumber' | 'customerName' | 'grandTotal' | 'orderStatus' | 'paymentStatus' | 'paymentConfirmationStatus' | 'createdAt'
> & {
    itemsCount?: number;
    previewItems?: { productName: string; imageUrl?: string | null; qty: number }[];
    canReview?: boolean;
};
