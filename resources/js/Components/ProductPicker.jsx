import React, { useState } from "react";
import { ResourcePicker } from "@shopify/app-bridge-react";

function ProductPicker({ config }) {
    const [isPickerOpen, setPickerOpen] = useState(false);
    const [selectedProducts, setSelectedProducts] = useState([]);

    const handleSelection = (resources) => {
        setSelectedProducts(resources.selection);
        setPickerOpen(false);
    };

    return (
        <Provider config={config}>
            <button onClick={() => setPickerOpen(true)}>Select Products</button>
            {isPickerOpen && (
                <ResourcePicker
                    resourceType="Product"
                    open
                    onSelection={handleSelection}
                    onCancel={() => setPickerOpen(false)}
                />
            )}
            <ul>
                {selectedProducts.map((product) => (
                    <li key={product.id}>{product.title}</li>
                ))}
            </ul>
        </Provider>
    );
}

export default ProductPicker;
