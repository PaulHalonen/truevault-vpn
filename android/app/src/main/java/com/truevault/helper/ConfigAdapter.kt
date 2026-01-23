package com.truevault.helper

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import java.io.File

data class ConfigFile(
    val file: File,
    val name: String,
    val wasFixed: Boolean,
    val status: String
)

class ConfigAdapter(
    private val configs: MutableList<ConfigFile>,
    private val onImportClick: (ConfigFile) -> Unit
) : RecyclerView.Adapter<ConfigAdapter.ViewHolder>() {

    class ViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val ivIcon: ImageView = view.findViewById(R.id.ivConfigIcon)
        val tvName: TextView = view.findViewById(R.id.tvConfigName)
        val tvStatus: TextView = view.findViewById(R.id.tvConfigStatus)
        val btnImport: Button = view.findViewById(R.id.btnImport)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_config, parent, false)
        return ViewHolder(view)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val config = configs[position]
        
        holder.tvName.text = config.name
        holder.tvStatus.text = config.status
        
        // Change status color based on status
        val context = holder.itemView.context
        holder.tvStatus.setTextColor(
            if (config.wasFixed) {
                context.getColor(R.color.secondary)
            } else {
                context.getColor(R.color.text_secondary)
            }
        )
        
        holder.btnImport.setOnClickListener {
            onImportClick(config)
        }
    }

    override fun getItemCount() = configs.size

    fun updateConfigs(newConfigs: List<ConfigFile>) {
        configs.clear()
        configs.addAll(newConfigs)
        notifyDataSetChanged()
    }
}
