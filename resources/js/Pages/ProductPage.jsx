import React, { useState } from "react";
import { Button, Card } from "@shopify/polaris";
import ProductForm from "@/Components/ProductsForm";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import ProductTable from "@/Components/ProductTable";

// Import the ProductForm component

export default function ProductsPage() {
    const [isFormVisible, setIsFormVisible] = useState(false);
    const [isFormVisible1, setIsFormVisible1] = useState(false);

    const handleShowTableClick1 = () => {
        setIsFormVisible1((prevState) => !prevState);
    };

    const handleCreateFormClick = () => {
        setIsFormVisible((prevState) => !prevState);
    };

    return (
        <>
            <AuthenticatedLayout>
                <Card>
                    {/* Toggle between showing the form or the table */}
                    <Button primary onClick={handleCreateFormClick}>
                        {isFormVisible ? "Hide Form" : "Show Form"}
                    </Button>
                    <Button primary onClick={handleShowTableClick1}>
                        {isFormVisible1 ? "Hide Table" : "Show Table"}
                    </Button>

                    {/* Render ProductForm only when isFormVisible is true */}
                    {isFormVisible && <ProductForm />}
                    {isFormVisible1 && <ProductTable />}
                </Card>
            </AuthenticatedLayout>
        </>
    );
}
