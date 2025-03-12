import {
    FormLayout,
    TextField,
    Button,
    Card,
    Select,
    BlockStack,
    InlineStack,
    Box,
    Divider,
    Banner,
    Text,
} from "@shopify/polaris";
import React, { useState } from "react";
import toast from "react-hot-toast";
import { Tag } from "@shopify/polaris";

function ProductForm() {
    const [formData, setFormData] = useState({
        title: "",
        description: "",
        productType: "",
        tags: [],
        status: "DRAFT",
        optionName: "",
        optionValue: "",
    });

    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);

    const handleInputChange = (field, value) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
    };

    const handleSaveProduct = async () => {
        setErrors({});

        const {
            title,
            description,
            productType,
            status,
            optionName,
            optionValue,
        } = formData;


        if (
            !title ||
            !description ||
            !productType ||
            !status ||
            !optionName ||
            !optionValue
        ) {
            setErrors({
                general: "Please fill out all required fields.",
                title: !title && "Title is required.",
                description: !description && "Description is required.",
                productType: !productType && "Product Type is required.",
                status: !status && "Status is required.",
                optionName: !optionName && "Option Name is required.",
                optionValue: !optionValue && "Option Value is required.",
            });
            return;
        }

        setLoading(true);

        const productData = {
            title,
            description,
            productType,
            tags: formData.tags,
            status,
            optionName,
            optionValue,
        };
        console.log(productData);

        try {
            const response = await fetch(route("product.create"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(productData),
            });

            const result = await response.json();

            if (response.ok) {
                toast.success("Product saved successfully!");
                setFormData({
                    title: "",
                    description: "",
                    productType: "",
                    tags: [],
                    status: "DRAFT",
                    optionName: "",
                    optionValue: "",
                });
            } else {
                setErrors(
                    result.errors || { general: "Something went wrong." }
                );
                toast.error(result.message || "Failed to save the product.");
            }
        } catch (error) {
            setErrors({ general: "Network error. Please try again." });
            toast.error("Network error. Please try again.");
        } finally {
            setLoading(false);
        }
    };

    const handleTagsChange = (tags) => {
        setFormData((prev) => ({ ...prev, tags }));
    };

    return (
        <Card padding="800">
            <BlockStack gap="800">
                {errors.general && (
                    <Banner title="Error" status="critical">
                        <Text as="p" variant="bodyMd">
                            {errors.general}
                        </Text>
                    </Banner>
                )}

                <FormLayout>
                    <Box paddingBlockEnd="600">
                        <BlockStack gap="400">
                            <Text variant="headingMd" as="h2">
                                Product Information
                            </Text>
                            <Divider />
                            <TextField
                                label="Product Title"
                                value={formData.title}
                                requiredIndicator
                                onChange={(value) =>
                                    handleInputChange("title", value)
                                }
                                error={errors.title}
                                autoComplete="off"
                            />
                            <TextField
                                label="Product Description"
                                value={formData.description}
                                requiredIndicator
                                onChange={(value) =>
                                    handleInputChange("description", value)
                                }
                                error={errors.description}
                                multiline={4}
                                autoComplete="off"
                            />
                            <TextField
                                label="Product Type"
                                value={formData.productType}
                                requiredIndicator
                                onChange={(value) =>
                                    handleInputChange("productType", value)
                                }
                                error={errors.productType}
                                autoComplete="off"
                            />
                        </BlockStack>
                    </Box>

                    <Box paddingBlockEnd="600">
                        <BlockStack gap="400">
                            <Text variant="headingMd" as="h2">
                                Product Settings
                            </Text>
                            <Divider />
                            <InlineStack gap="400" blockAlign="start">
                                <Box minWidth="300px">
                                    <Select
                                        label="Status"
                                        options={[
                                            {
                                                label: "Active",
                                                value: "ACTIVE",
                                            },
                                            { label: "Draft", value: "DRAFT" },
                                            {
                                                label: "Archived",
                                                value: "ARCHIVED",
                                            },
                                        ]}
                                        onChange={(value) =>
                                            handleInputChange("status", value)
                                        }
                                        value={formData.status}
                                        error={errors.status}
                                    />
                                </Box>
                                <Box minWidth="300px">
                                    <TagInput
                                        tags={formData.tags}
                                        onTagsChange={handleTagsChange}
                                    />
                                </Box>
                            </InlineStack>
                        </BlockStack>
                    </Box>

                    <Box paddingBlockEnd="600">
                        <BlockStack gap="400">
                            <Text variant="headingMd" as="h2">
                                Product Options
                            </Text>
                            <Divider />
                            <InlineStack gap="400" blockAlign="start">
                                <TextField
                                    label="Option Name"
                                    value={formData.optionName}
                                    requiredIndicator
                                    onChange={(value) =>
                                        handleInputChange("optionName", value)
                                    }
                                    error={errors.optionName}
                                    autoComplete="off"
                                />
                                <TextField
                                    label="Option Value"
                                    value={formData.optionValue}
                                    requiredIndicator
                                    onChange={(value) =>
                                        handleInputChange("optionValue", value)
                                    }
                                    error={errors.optionValue}
                                    autoComplete="off"
                                />
                            </InlineStack>
                        </BlockStack>
                    </Box>

                    <Box paddingBlockStart="200">
                        <Button
                            primary
                            onClick={handleSaveProduct}
                            loading={loading}
                        >
                            Save Product
                        </Button>
                    </Box>
                </FormLayout>
            </BlockStack>
        </Card>
    );
}

const TagInput = ({ tags, onTagsChange }) => {
    const [inputValue, setInputValue] = useState("");

    const handleTagSubmit = () => {
        const trimmedValue = inputValue.trim();
        if (trimmedValue && !tags.includes(trimmedValue)) {
            onTagsChange([...tags, trimmedValue]);
            setInputValue("");
        }
    };

    const handleTagRemove = (index) => {
        onTagsChange(tags.filter((_, i) => i !== index));
    };

    return (
        <BlockStack gap="200">
            <InlineStack gap="200" blockAlign="center">
                <TextField
                    value={inputValue}
                    onChange={setInputValue}
                    onKeyDown={(e) => e.key === "Enter" && handleTagSubmit()}
                    placeholder="Add tags..."
                    autoComplete="off"
                    connectedRight={
                        <Button variant="primary" onClick={handleTagSubmit}>
                            Add
                        </Button>
                    }
                />
            </InlineStack>

            {tags.length > 0 && (
                <InlineStack gap="100" wrap>
                    {tags.map((tag, index) => (
                        <Tag
                            key={`${tag}-${index}`}
                            onRemove={() => handleTagRemove(index)}
                        >
                            {tag}
                        </Tag>
                    ))}
                </InlineStack>
            )}
        </BlockStack>
    );
};

export default ProductForm;
