import React, { useState } from "react";
import { Button, Card } from "@shopify/polaris";
import OrdersTable from "@/Components/OrdersTable";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

// Import the ProductForm component

export default function ProductsPage() {
    const [isFormVisible1, setIsFormVisible1] = useState(false);

    const handleShowTableClick1 = () => {
        setIsFormVisible1((prevState) => !prevState);
    };

    return (
        <>
            <AuthenticatedLayout>
                <Card>
                    {/* Toggle between showing the form or the table */}

                    <Button primary onClick={handleShowTableClick1}>
                        {isFormVisible1
                            ? "Hide Orders Table"
                            : "Show Orders  Table"}
                    </Button>

                    {/* Render ProductForm only when isFormVisible is true */}

                    {isFormVisible1 && <OrdersTable />}

                    {/* {isFormVisible1 && <ProductTable />} */}
                </Card>
            </AuthenticatedLayout>
        </>
    );
}
