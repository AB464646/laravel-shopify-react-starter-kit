import {
    ChoiceList,
    Card,
    Filters,
    DataTable,
    Button,
    Select,
    Pagination,
} from "@shopify/polaris";
import { useState, useCallback, useEffect } from "react";
import { toast } from "react-hot-toast";
import { router, usePage } from "@inertiajs/react";

export default function ProductTable() {
    const [products, setProducts] = useState([]);
    const [productStatus, setProductStatus] = useState([]);
    const [queryValue, setQueryValue] = useState("");
    const [rows, setRows] = useState([]);
    const [errors, setErrors] = useState(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const page = usePage().props;
    const { query } = page.ziggy;

    const fetchProducts = async (page = 1) => {
        try {
            const response = await fetch(
                route("product.get", {
                    query: queryValue,
                    status: productStatus,
                    page: page,
                }),
                {
                    method: "GET",
                    headers: { "Content-Type": "application/json" },
                }
            );

            const results = await response.json();
            if (results.success) {
                const transformedRows = results.data.products.map((product) => [
                    product.id,
                    product.title,
                    product.description,
                    product.product_type,
                    product.tags,
                    product.status,
                    product.total_orders,
                ]);
                setRows(transformedRows);
                setProducts(results.data.products);
                setTotalPages(results.data.total_pages);
                toast.success("Products fetched successfully!");
            } else {
                setErrors(results.errors);
                toast.error("Failed to fetch products.");
            }
        } catch (error) {
            setErrors(error.message);
            toast.error(`Error: ${error.message}`);
        }
    };

    const updateProductStatus = async (productId, newStatus) => {
        try {
            const response = await fetch(
                route("product.updateStatus", { id: productId }),
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ status: newStatus }),
                }
            );

            const result = await response.json();
            if (result.success) {
                toast.success("Product status updated successfully!");
                fetchProducts(currentPage);
            } else {
                toast.error("Failed to update product status.");
            }
        } catch (error) {
            toast.error(`Error: ${error.message}`);
        }
    };

    useEffect(() => {
        fetchProducts(currentPage);
    }, [queryValue, productStatus, currentPage]);

    const handleProductStatusChange = useCallback((value) => {
        setProductStatus(value);
    }, []);

    const handleFiltersQueryChange = useCallback((value) => {
        setQueryValue(value);
    }, []);

    const handleProductStatusRemove = useCallback(() => {
        setProductStatus([]);
    }, []);

    const handleQueryValueRemove = useCallback(() => {
        setQueryValue("");
    }, []);

    const handleFiltersClearAll = useCallback(() => {
        handleProductStatusRemove();
        handleQueryValueRemove();
    }, [handleQueryValueRemove, handleProductStatusRemove]);

    const filters = [
        {
            key: "productStatus",
            label: "Product Status",
            filter: (
                <ChoiceList
                    title="Product Status"
                    titleHidden
                    choices={[
                        { label: "ACTIVE", value: "ACTIVE" },
                        { label: "DRAFT", value: "DRAFT" },
                        { label: "ARCHIVED", value: "ARCHIVED" },
                    ]}
                    selected={productStatus}
                    onChange={handleProductStatusChange}
                    allowMultiple
                />
            ),
        },
    ];

    const appliedFilters = [];

    if (!isEmpty(productStatus)) {
        const key = "productStatus";
        appliedFilters.push({
            key,
            label: disambiguateLabel(key, productStatus),
            onRemove: handleProductStatusRemove,
        });
    }

    const statusOptions = [
        { label: "ACTIVE", value: "ACTIVE" },
        { label: "DRAFT", value: "DRAFT" },
        { label: "ARCHIVED", value: "ARCHIVED" },
    ];

    const rowsWithStatusUpdate = rows.map((row) => {
        const [id, title, description, productType, tags, status, totalOrders] =
            row;
        return [
            id,
            title,
            description,
            productType,
            tags,
            <Select
                options={statusOptions}
                value={status}
                onChange={(newStatus) => updateProductStatus(id, newStatus)}
            />,
            totalOrders,
        ];
    });

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
                {/* <Button onClick={confirmSync}>Sync Products</Button> */}
                <DataTable
                    columnContentTypes={[
                        "text", // ID
                        "text", // Product Name
                        "text", // Description
                        "text", // Product Type
                        "text", // Tags
                        "text", // Status
                        "text", // Total Orders
                    ]}
                    headings={[
                        "ID",
                        "Product Name",
                        "Description",
                        "Product Type",
                        "Tags",
                        "Status",
                        "Total Orders",
                    ]}
                    rows={rowsWithStatusUpdate}
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
            case "productStatus":
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
