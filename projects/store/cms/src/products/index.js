
export const add_product = (product) => {
    return fetch("http://localhost:8080/products/add",{
        method:"POST",
        headers:{
            Accept:"application/json",
            'Content-Type':"application/json"
        },
        body: JSON.stringify(product)
    })
    .then(response => {
        return response.json();
    })
    .catch(err => console.log(err));
}

export const update_product = (product,id) => {
    let url = `http://localhost:8080/product/${id}`;
    // console.log(product);
    return fetch(url,{
        method:"PUT",
        headers:{
            Accept:"application/json",
            'Content-Type':"application/json"
        },
        body: JSON.stringify(product)
    })
    .then(response => {
        return response.json();
    })
    .catch(err => console.log(err));
}

export const delete_product = (id) => {
    let url = `http://localhost:8080/product/${id}`;
    // console.log(product);
    return fetch(url,{
        method:"DELETE",
        headers:{
            Accept:"application/json",
            'Content-Type':"application/json"
        },
        body: ""
    })
    .then(response => {
        return response.json();
    })
    .catch(err => console.log(err));
}
