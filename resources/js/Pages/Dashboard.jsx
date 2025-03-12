import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { Button, Card, Grid, Select, Spinner, TextField } from '@shopify/polaris';
import { useState } from 'react';
import toast from 'react-hot-toast';
import MonacoEditor from "@monaco-editor/react";

export default function Dashboard() {

    const [disabled, setDisabled] = useState(false);

    const [hideProductId, setHideProductId] = useState(true)

    const [hideProductHandle, setHideProductHandle] = useState(true)

    const [productId, setProductId] = useState();

    const [productHandle, setProductHandle] = useState();

    const [productQueries, setProductQueries] = useState([
        { label: 'Get Products', value: 'all' },
        { label: 'Get Product By Id', value: 'by_id' },
        { label: 'Get Product By Handle', value: 'by_handle' },
    ])

    const [Queries, setQueries] = useState([
        { label: 'Products', value: 'products' },
        { label: 'Product Variants', value: 'variants' },
    ])

    const [fetchedProducts, setFetchedProducts] = useState()

    const [selectedProductQuery, setSelectedProductQuery] = useState('');

    const [selectedQuery, setSelectedQuery] = useState('');

    const handleProductQueryChange = (value) => {
        setSelectedProductQuery(value);
        setFetchedProducts();
        setHideProductId(true);
        setProductId();
        setHideProductHandle(true);
        setProductHandle();
        if (value === "all") {
            setDisabled(true);
            const promise = new Promise((resolve, reject) => {
                setTimeout(async () => {
                    await fetch(route("product.get"), {
                        method: "GET",
                    })
                        .then(async (response) => {
                            var results = await response.json();
                            if (results.success) {
                                setFetchedProducts(results.data);
                                resolve(results.message);
                            } else {
                                reject(results.message);
                            }
                        })
                        .catch(reject);
                }, 2000);
            });
            toast
                .promise(
                    promise,
                    {
                        loading: "Fetching Products.......",
                        success: (data) => `${data} Successfully`,
                        error: (err) =>
                            `This just happened because: ${err.toString()}`,
                    },
                    {
                        style: {
                            minWidth: "250px",
                        },
                        success: {
                            duration: 5000,
                        },
                        error: {
                            duration: 5000,
                        },
                    }
                )
                .then(() => {
                    setDisabled(false);
                })
                .catch((error) => {
                    setDisabled(false);
                    console.error("An error occurred:", error);
                });
        }
        if (value === "by_id") {
            setHideProductId(false);
        }
        if (value === "by_handle") {
            setHideProductHandle(false);
        }
    };

    const handleFetchProductById = () => {
        setDisabled(true);
        const promise = new Promise((resolve, reject) => {
            setTimeout(async () => {
                await fetch(route("get.product", { id: productId }), {
                    method: "GET",
                })
                    .then(async (response) => {
                        var results = await response.json();
                        if (results.success) {
                            setFetchedProducts(results.data);
                            resolve(results.message);
                        } else {
                            reject(results.message);
                        }
                    })
                    .catch(reject);
            }, 2000);
        });
        toast
            .promise(
                promise,
                {
                    loading: "Fetching Product.......",
                    success: (data) => `${data} Successfully`,
                    error: (err) =>
                        `This just happened because: ${err.toString()}`,
                },
                {
                    style: {
                        minWidth: "250px",
                    },
                    success: {
                        duration: 5000,
                    },
                    error: {
                        duration: 5000,
                    },
                }
            )
            .then(() => {
                setDisabled(false);
            })
            .catch((error) => {
                setDisabled(false);
                console.error("An error occurred:", error);
            });
    };

    const handleFetchProductByHandle = () => {
        setDisabled(true);
        const promise = new Promise((resolve, reject) => {
            setTimeout(async () => {
                await fetch(
                    route("get.product.handle", { handle: productHandle }),
                    {
                        method: "GET",
                    }
                )
                    .then(async (response) => {
                        var results = await response.json();
                        if (results.success) {
                            setFetchedProducts(results.data);
                            resolve(results.message);
                        } else {
                            reject(results.message);
                        }
                    })
                    .catch(reject);
            }, 2000);
        });
        toast
            .promise(
                promise,
                {
                    loading: "Fetching Product.......",
                    success: (data) => `${data} Successfully`,
                    error: (err) =>
                        `This just happened because: ${err.toString()}`,
                },
                {
                    style: {
                        minWidth: "250px",
                    },
                    success: {
                        duration: 5000,
                    },
                    error: {
                        duration: 5000,
                    },
                }
            )
            .then(() => {
                setDisabled(false);
            })
            .catch((error) => {
                setDisabled(false);
                console.error("An error occurred:", error);
            });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            You're logged in!
                        </div>
                    </div>
                    <Card>
                        <Select
                            label="Queries"
                            options={Queries}
                            onChange={setSelectedQuery}
                            value={selectedQuery}
                            placeholder="Select a Query"
                        />
                    </Card>
                    {selectedQuery === "products" ? (
                        <Card>
                            <h3 className="text-lg font-medium leading-6 text-gray-900 my-4">
                                Products
                            </h3>
                            <Grid>
                                <Grid.Cell
                                    columnSpan={{
                                        xs: 6,
                                        sm: 6,
                                        md: 6,
                                        lg: 6,
                                        xl: 6,
                                    }}
                                >
                                    <Card title="Sales" sectioned>
                                        <Select
                                            label="Product Queries"
                                            options={productQueries}
                                            onChange={handleProductQueryChange}
                                            value={selectedProductQuery}
                                            placeholder="Select a product query"
                                            disabled={disabled}
                                        />
                                    </Card>
                                    {!hideProductId && (
                                        <Card>
                                            <TextField
                                                label="Product ID"
                                                type="number"
                                                placeholder="Enter Product ID"
                                                disabled={disabled}
                                                value={productId}
                                                onChange={setProductId}
                                            />
                                            <Button
                                                primary
                                                loading={disabled}
                                                onClick={handleFetchProductById}
                                            >
                                                Submit
                                            </Button>
                                        </Card>
                                    )}
                                    {!hideProductHandle && (
                                        <Card>
                                            <TextField
                                                label="Product Handle"
                                                type="text"
                                                placeholder="Enter Product Handle"
                                                disabled={disabled}
                                                value={productHandle}
                                                onChange={setProductHandle}
                                            />
                                            <Button
                                                primary
                                                loading={disabled}
                                                onClick={
                                                    handleFetchProductByHandle
                                                }
                                            >
                                                Submit
                                            </Button>
                                        </Card>
                                    )}
                                </Grid.Cell>
                                <Grid.Cell
                                    columnSpan={{
                                        xs: 6,
                                        sm: 6,
                                        md: 6,
                                        lg: 6,
                                        xl: 6,
                                    }}
                                >
                                    <Card title="Orders" sectioned>
                                        {fetchedProducts ? (
                                            <MonacoEditor
                                                height="500px"
                                                defaultLanguage="json"
                                                theme="vs-dark"
                                                defaultValue={JSON.stringify(
                                                    fetchedProducts,
                                                    null,
                                                    2
                                                )}
                                            />
                                        ) : (
                                            "Nothing to Show"
                                        )}
                                    </Card>
                                </Grid.Cell>
                            </Grid>
                        </Card>
                    ) : selectedQuery === "variants" ? (
                        <Card>
                            <h3 className="text-lg font-medium leading-6 text-gray-900 my-4">
                                Variants
                            </h3>
                        </Card>
                    ) : null}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
