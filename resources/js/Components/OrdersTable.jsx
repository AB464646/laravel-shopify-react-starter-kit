import {
    ChoiceList,
    Card,
    Filters,
    DataTable,
    Button,
    Pagination,
} from "@shopify/polaris";
import { useState, useCallback, useEffect } from "react";
import { toast } from "react-hot-toast";
import { router, usePage } from "@inertiajs/react";

export default function OrderTable() {
    const [orders, setOrders] = useState([]);
    const [orderStatus, setOrderStatus] = useState([]);
    const [queryValue, setQueryValue] = useState("");
    const [rows, setRows] = useState([]);
    const [errors, setErrors] = useState(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const page = usePage().props;
    const { query } = page.ziggy;

    const fetchOrders = async (page = 1) => {
        try {
            const response = await fetch(
                route("orders.get", {
                    query: queryValue,
                    status: orderStatus,
                    page: page,
                }),
                {
                    method: "GET",
                    headers: { "Content-Type": "application/json" },
                }
            );

            const results = await response.json();
            console.log(results);
            if (results.success) {
                const transformedRows = results.data.orders.map((order) => [
                    order.id,
                    order.order_number,
                    order.financial_status,
                    order.products.map((p) => p.product_name).join(","),
                ]);
                setRows(transformedRows);
                setOrders(results.data.orders);
                setTotalPages(results.data.total_pages);
            } else {
                setErrors(results.errors);
            }
        } catch (error) {
            setErrors(error.message);
            toast.error(`Error: ${error.message}`);
        }
    };

    useEffect(() => {
        fetchOrders(currentPage);
    }, [queryValue, orderStatus, currentPage]);

    const handleOrderStatusChange = useCallback((value) => {
        setOrderStatus(value);
    }, []);

    const handleFiltersQueryChange = useCallback((value) => {
        setQueryValue(value);
    }, []);

    const handleOrderStatusRemove = useCallback(() => {
        setOrderStatus([]);
    }, []);

    const handleQueryValueRemove = useCallback(() => {
        setQueryValue("");
    }, []);

    const handleFiltersClearAll = useCallback(() => {
        handleOrderStatusRemove();
        handleQueryValueRemove();
    }, [handleQueryValueRemove, handleOrderStatusRemove]);

    const filters = [
        {
            key: "orderStatus",
            label: "Order Status",
            filter: (
                <ChoiceList
                    title="Order Status"
                    titleHidden
                    choices={[
                        { label: "AUTHORIZED", value: "AUTHORIZED" },
                        { label: "EXPIRED", value: "EXPIRED" },
                        { label: "PAID", value: "PAID" },
                        { label: "PARTIALLY PAID", value: "PARTIALLY_PAID" },
                        {
                            label: "PARTIALLY REFUNDED",
                            value: "PARTIALLY_REFUNDED",
                        },
                        { label: "PENDING", value: "PENDING" },
                        { label: "REFUNDED", value: "REFUNDED" },
                        { label: "VOIDED", value: "VOIDED" },
                    ]}
                    selected={orderStatus}
                    onChange={handleOrderStatusChange}
                    allowMultiple
                />
            ),
        },
    ];

    const appliedFilters = [];

    if (!isEmpty(orderStatus)) {
        const key = "orderStatus";
        appliedFilters.push({
            key,
            label: disambiguateLabel(key, orderStatus),
            onRemove: handleOrderStatusRemove,
        });
    }

    return (
        <div style={{ height: "568px" }}>
            <Card>
                <Filters
                    queryValue={queryValue}
                    queryPlaceholder="Search items"
                    filters={filters}
                    appliedFilters={appliedFilters}
                    onQueryChange={handleFiltersQueryChange}
                    onQueryClear={handleQueryValueRemove}
                    onClearAll={handleFiltersClearAll}
                />
                {/* <Button onClick={confirmSync}>Sync Orders</Button> */}
                <DataTable
                    columnContentTypes={["text", "text", "text", "text"]}
                    headings={[
                        "ID",
                        "Order Number",
                        "Financial Status",
                        "Products",
                    ]}
                    rows={rows}
                />
                <Pagination
                    hasPrevious={currentPage > 1}
                    onPrevious={() => setCurrentPage(currentPage - 1)}
                    hasNext={currentPage < totalPages}
                    onNext={() => setCurrentPage(currentPage + 1)}
                />
            </Card>
        </div>
    );

    function disambiguateLabel(value, key) {
        switch (key) {
            case "OrderStatus":
                return value.join(", ");
            default:
                return value.toString();
        }
    }

    function isEmpty(value) {
        if (Array.isArray(value)) {
            return value.length === 0;
        } else {
            return value === "" || value == null;
        }
    }
}
