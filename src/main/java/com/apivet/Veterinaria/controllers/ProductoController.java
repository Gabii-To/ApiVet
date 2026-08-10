package com.apivet.Veterinaria.controllers;

import com.apivet.Veterinaria.entidades.Producto;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.ArrayList;
import java.util.List;

@RestController
@RequestMapping("/productos")
public class ProductoController {

    @GetMapping
    public List<Producto> getTodosLosProductos() {
        List<Producto> productos = new ArrayList<>();

        return productos;
    }
}
